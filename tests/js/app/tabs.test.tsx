import { afterEach, describe, expect, it } from 'vitest'
import { cleanup, fireEvent, render, screen } from '@testing-library/react'
import type { Api } from '@/shared/api'
import App from '@/app/App'
import { initialTab } from '@/app/tabs'
import { cfg, mockApi } from './fixtures'

afterEach(() => {
  cleanup()
  window.history.replaceState(null, '', '/')
})

describe('initialTab', () => {
  it.each([
    ['#display', 'display'],
    ['#style', 'style'],
    ['', 'general'],
    ['#unknown', 'general'],
  ])('opens %j as %s', (hash, tab) => {
    expect(initialTab(hash)).toBe(tab)
  })
})

describe('App tabs', () => {
  const selected = (name: string) => screen.getByRole('tab', { name }).getAttribute('aria-selected')

  it('opens the tab the URL hash names', () => {
    window.history.replaceState(null, '', '#style')
    render(<App cfg={cfg} api={mockApi() as Api} />)

    expect(selected('Style')).toBe('true')
    expect(selected('General')).toBe('false')
  })

  it('writes the picked tab to the hash without a history entry', () => {
    render(<App cfg={cfg} api={mockApi() as Api} />)
    const entries = window.history.length

    fireEvent.click(screen.getByRole('tab', { name: 'Display' }))

    expect(selected('Display')).toBe('true')
    expect(window.location.hash).toBe('#display')
    expect(window.history.length).toBe(entries)
  })

  it('keeps a draft across tab switches', () => {
    render(<App cfg={cfg} api={mockApi() as Api} />)

    fireEvent.change(screen.getAllByLabelText('Text before')[0], { target: { value: 'As low as' } })
    fireEvent.click(screen.getByRole('tab', { name: 'Style' }))
    fireEvent.click(screen.getByRole('tab', { name: 'General' }))

    expect(screen.getAllByLabelText<HTMLInputElement>('Text before')[0].value).toBe('As low as')
  })
})
