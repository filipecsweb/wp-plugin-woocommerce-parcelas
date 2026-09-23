import { afterEach, describe, expect, it } from 'vitest'
import { cleanup, fireEvent, render, screen, waitFor, within } from '@testing-library/react'
import type { Api } from '@/shared/api'
import App from '@/app/App'
import type { Settings } from '@/app/store'
import { cfg, mockApi, settings } from './fixtures'

afterEach(() => {
  cleanup()
  window.history.replaceState(null, '', '/')
})

const root = () => document.querySelector<HTMLElement>('.installment-prices-admin')!.dataset
const card = (title: string) => screen.getByRole('heading', { name: title }).closest<HTMLElement>('[data-slot="card"]')!
const saveButton = () => screen.getByRole<HTMLButtonElement>('button', { name: /^(Save settings|Saving…)$/ })

const renderApp = () => {
  const api = mockApi()
  render(<App cfg={cfg} api={api as Api} />)
  return api
}

describe('App', () => {
  it('renders the root contract clean, with the saved settings in the fields', () => {
    renderApp()

    expect(root()).toMatchObject({ busySave: 'false', dirty: 'false' })
    expect(within(card('Installment price')).getByLabelText<HTMLInputElement>('Text before').value).toBe('Up to')
    expect(within(card('Cash price')).getByRole('checkbox', { name: 'Show the cash price' }).getAttribute('aria-checked')).toBe('false')
  })

  it('marks an edit unsaved, and a number typed back to its value as no change', () => {
    renderApp()
    const max = within(card('Installment price')).getByLabelText('Maximum installments')

    fireEvent.change(max, { target: { value: '12' } })
    expect(root().dirty).toBe('true')
    expect(screen.getByText('You have unsaved changes.')).toBeTruthy()

    fireEvent.change(max, { target: { value: '10' } })
    expect(root().dirty).toBe('false')
  })

  it('saves the whole draft, adopts the server’s copy, and toasts', async () => {
    const api = renderApp()
    const corrected: Settings = { ...settings, installments: { ...settings.installments, max: 2 } }
    api.mockResolvedValueOnce(corrected)

    fireEvent.change(within(card('Installment price')).getByLabelText('Maximum installments'), { target: { value: '1' } })
    fireEvent.click(saveButton())

    expect(api).toHaveBeenCalledWith('POST', '/settings', expect.objectContaining({ installments: expect.objectContaining({ max: '1' }) }))
    expect(root().busySave).toBe('true')
    expect(await screen.findByText('Settings saved.', { selector: '[data-testid="toast-success"] *' })).toBeTruthy()
    await waitFor(() => expect(root()).toMatchObject({ busySave: 'false', dirty: 'false' }))
    expect(within(card('Installment price')).getByLabelText<HTMLInputElement>('Maximum installments').value).toBe('2')
  })

  it('warns when the server clears a style value it couldn’t read', async () => {
    const api = renderApp()
    api.mockResolvedValueOnce(settings)
    fireEvent.click(screen.getByRole('tab', { name: 'Style' }))

    fireEvent.change(within(card('Installment price')).getByRole('textbox', { name: 'Product lists, Text before: Size' }), { target: { value: '18 pixels' } })
    fireEvent.click(saveButton())

    expect(await screen.findByText(/weren’t valid/, { selector: '[data-testid="toast-error"] *' })).toBeTruthy()
  })

  it('keeps the edits and toasts the error when a save fails', async () => {
    const api = renderApp()
    api.mockRejectedValueOnce({ code: 'rest_forbidden', message: 'Not allowed.', status: 403 })

    fireEvent.change(within(card('Installment price')).getByLabelText('Text after'), { target: { value: 'no interest' } })
    fireEvent.click(saveButton())

    expect(await screen.findByText('Not allowed.', { selector: '[data-testid="toast-error"] *' })).toBeTruthy()
    expect(root().dirty).toBe('true')
  })

  it('edits placement and style through the store', () => {
    renderApp()

    fireEvent.click(screen.getByRole('tab', { name: 'Display' }))
    fireEvent.change(within(card('Product page')).getByLabelText('Alignment'), { target: { value: 'center' } })
    fireEvent.click(screen.getByRole('tab', { name: 'Style' }))
    fireEvent.change(within(card('Cash price')).getByRole('textbox', { name: 'Product page, Amount: Color' }), { target: { value: '#cc1818' } })
    fireEvent.click(within(card('Cash price')).getByRole('button', { name: 'Product page, Amount: Color — use the theme color' }))

    expect(root().dirty).toBe('true')
    expect(within(card('Cash price')).getByRole<HTMLInputElement>('textbox', { name: 'Product page, Amount: Color' }).value).toBe('')
  })

  it('renders the footer from the config', () => {
    renderApp()

    const footer = document.querySelector('footer')!
    expect(footer.textContent).toContain('Installment Prices for WooCommerce')
    expect(footer.textContent).toContain('Version 2.0.0')
    expect(within(footer).getByRole('link', { name: 'Get support' }).getAttribute('href')).toBe(cfg.supportUrl)
  })
})
