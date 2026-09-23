/**
 * Settings-screen state: the saved settings, the working copy the forms edit, and
 * the save that turns one into the other.
 *
 * CONTRACT: handlers take their inputs as arguments and dispatch results; they never
 * read state after an await.
 *
 * @since 2.0.0
 */
import { useMemo, useReducer, type Dispatch } from 'react'
import { __ } from '@wordpress/i18n'
import type { Api, ApiFailure } from '@/shared/api'

export type Kind = 'installments' | 'cash'
export type Context = 'loop' | 'single'
export type Part = 'prefix' | 'amount' | 'suffix'

// A number field holds what was typed until the save; the server reads either form.
export type Num = number | string

/** Settings::sanitize(), which every key below mirrors. */
export interface Installments {
  enabled: boolean
  prefix: string
  max: Num
  suffix: string
  min_amount: Num
  out_of_stock: boolean
}

export interface Cash {
  enabled: boolean
  prefix: string
  discount: Num
  discount_type: string
  suffix: string
  out_of_stock: boolean
}

export interface Placement {
  hook: string
  priority: Num
  align: string
}

export interface PartStyle {
  color: string
  weight: string
  size: string
}

export interface Settings {
  installments: Installments
  cash: Cash
  placement: Record<Context, Placement>
  style: Record<Kind, Record<Context, Record<Part, PartStyle>>>
}

export interface Choice {
  value: string
  label: string
}

/** Settings::choices() */
export interface Choices {
  loopHooks: Choice[]
  singleHooks: Choice[]
  alignments: Choice[]
  weights: Choice[]
  discountTypes: Choice[]
}

/** The keys of window.InstallmentPricesConfig the React screen reads (AdminServiceProvider::config()). */
export interface Config {
  restNamespace: string
  plugin: { name: string; version: string }
  settings: Settings
  choices: Choices
  currency: string
  supportUrl: string
}

export interface State {
  saved: Settings
  draft: Settings
  busy: boolean
}

export type Action =
  | { type: 'installments'; patch: Partial<Installments> }
  | { type: 'cash'; patch: Partial<Cash> }
  | { type: 'placement'; context: Context; patch: Partial<Placement> }
  | { type: 'style'; kind: Kind; context: Context; part: Part; patch: Partial<PartStyle> }
  | { type: 'busy'; value: boolean }
  | { type: 'saved'; settings: Settings }

export function initialState(cfg: Config): State {
  return { saved: cfg.settings, draft: cfg.settings, busy: false }
}

export function reducer(state: State, action: Action): State {
  const draft = state.draft
  switch (action.type) {
    case 'installments':
      return { ...state, draft: { ...draft, installments: { ...draft.installments, ...action.patch } } }
    case 'cash':
      return { ...state, draft: { ...draft, cash: { ...draft.cash, ...action.patch } } }
    case 'placement':
      return { ...state, draft: { ...draft, placement: { ...draft.placement, [action.context]: { ...draft.placement[action.context], ...action.patch } } } }
    case 'style': {
      const kind = draft.style[action.kind]
      const context = kind[action.context]
      const part = { ...context[action.part], ...action.patch }
      return { ...state, draft: { ...draft, style: { ...draft.style, [action.kind]: { ...kind, [action.context]: { ...context, [action.part]: part } } } } }
    }
    case 'busy':
      return { ...state, busy: action.value }
    case 'saved':
      // The server's copy is the validated one (e.g. a maximum below 2 comes back as 2).
      return { ...state, saved: action.settings, draft: action.settings }
  }
}

// "10" typed into a field that held 10 is no change.
const comparable = (settings: Settings) => JSON.stringify(settings, (_key, value: unknown) => (typeof value === 'number' ? String(value) : value))

export const isDirty = (s: State): boolean => comparable(s.saved) !== comparable(s.draft)

// A style value the server couldn't read comes back empty; say so, or it would just vanish.
const clearedStyles = (sent: Settings, saved: Settings): boolean =>
  Object.entries(sent.style).some(([kind, contexts]) =>
    Object.entries(contexts).some(([context, parts]) =>
      Object.entries(parts).some(([part, style]) =>
        Object.entries(style).some(([key, value]) => value.trim() !== '' && saved.style[kind as Kind][context as Context][part as Part][key as keyof PartStyle] === '')
      )
    )
  )

export type Notify = (type: 'success' | 'error', text: string) => void

export function createActions(dispatch: Dispatch<Action>, api: Api, notify: Notify) {
  return {
    installments: (patch: Partial<Installments>) => dispatch({ type: 'installments', patch }),
    cash: (patch: Partial<Cash>) => dispatch({ type: 'cash', patch }),
    placement: (context: Context, patch: Partial<Placement>) => dispatch({ type: 'placement', context, patch }),
    style: (kind: Kind, context: Context, part: Part, patch: Partial<PartStyle>) => dispatch({ type: 'style', kind, context, part, patch }),

    async save(draft: Settings): Promise<void> {
      dispatch({ type: 'busy', value: true })
      try {
        const settings = await api<Settings>('POST', '/settings', draft)
        dispatch({ type: 'saved', settings })
        notify('success', __('Settings saved.', 'woocommerce-parcelas'))
        if (clearedStyles(draft, settings)) {
          notify('error', __('Some style values weren’t valid, so they were cleared. Use colors like #1e1e1e and sizes like 18px or 1.2em.', 'woocommerce-parcelas'))
        }
      } catch (e) {
        notify('error', (e as ApiFailure).message || __('Something went wrong. Please try again.', 'woocommerce-parcelas'))
      } finally {
        dispatch({ type: 'busy', value: false })
      }
    },
  }
}

export type Actions = ReturnType<typeof createActions>

export function useStore(cfg: Config, api: Api, notify: Notify): { state: State; actions: Actions } {
  const [state, dispatch] = useReducer(reducer, cfg, initialState)
  const actions = useMemo(() => createActions(dispatch, api, notify), [api, notify])
  return { state, actions }
}
