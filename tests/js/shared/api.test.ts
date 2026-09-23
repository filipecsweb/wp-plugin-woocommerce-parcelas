import { afterEach, describe, expect, it, vi } from 'vitest'
import { createApi, toApiFailure } from '@/shared/api'

// The real apiFetch runs (its default middlewares included); only the transport is stubbed.
const fetchMock = vi.fn<typeof fetch>()
vi.stubGlobal('fetch', fetchMock)

const json = (body: unknown, status = 200) =>
  new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } })

const api = createApi('installment-prices-for-woocommerce/v1')

afterEach(() => fetchMock.mockReset())

describe('createApi', () => {
  it('prefixes the namespace and sends JSON', async () => {
    fetchMock.mockResolvedValueOnce(json({ ok: true }))

    await expect(api('POST', '/settings', { installments: { max: 3 } })).resolves.toEqual({ ok: true })

    const [url, init] = fetchMock.mock.calls[0] as [string, RequestInit]
    expect(url).toMatch(/^\/installment-prices-for-woocommerce\/v1\/settings(\?|$)/)
    expect(init.method).toBe('POST')
    expect(init.body).toBe('{"installments":{"max":3}}')
  })

  it('normalises a WP_Error body to code, status and message', async () => {
    fetchMock.mockResolvedValueOnce(json({ code: 'rest_forbidden', message: 'Not allowed', data: { status: 403 } }, 403))

    await expect(api('GET', '/settings')).rejects.toEqual({ code: 'rest_forbidden', status: 403, message: 'Not allowed' })
  })

  it('reports a transport failure without a status', async () => {
    fetchMock.mockRejectedValueOnce(new TypeError('Failed to fetch'))

    const failure = await api('GET', '/settings').catch((e: unknown) => e)
    expect(failure).toMatchObject({ code: expect.stringMatching(/^(fetch|offline)_error$/) })
    expect(failure).not.toHaveProperty('status')
  })

  it('reports a non-JSON error body without a status', async () => {
    fetchMock.mockResolvedValueOnce(new Response('<html>fatal</html>', { status: 500 }))

    await expect(api('GET', '/settings')).rejects.toEqual({ code: 'invalid_json', message: expect.any(String) })
  })
})

describe('toApiFailure', () => {
  it.each([
    [{ code: 'x', message: 'm', data: { status: 401 } }, { code: 'x', message: 'm', status: 401 }],
    [{ code: 'x', message: 'm', data: { status: '401' } }, { code: 'x', message: 'm' }],
    [{ message: 'm' }, { code: '', message: 'm' }],
    [new Error('boom'), { code: '', message: 'boom' }],
    ['string', { code: '', message: '' }],
    [null, { code: '', message: '' }],
  ])('%j → %j', (input, expected) => {
    expect(toApiFailure(input)).toEqual(expected)
  })
})
