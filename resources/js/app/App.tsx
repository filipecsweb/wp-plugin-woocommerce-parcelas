/**
 * Settings screen shell: the store, the tabs kept in sync with the URL hash, Save,
 * the host every portalled primitive renders into, and the footer.
 *
 * @since 2.0.0
 */
import { useState } from 'react'
import { __, sprintf } from '@wordpress/i18n'
import type { Api } from '@/shared/api'
import { Button } from '@/ui/button'
import { PortalContainer } from '@/ui/portal'
import { Spinner } from '@/ui/spinner'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/ui/tabs'
import { TextLink } from '@/ui/text-link'
import DisplayTab from './DisplayTab'
import GeneralTab from './GeneralTab'
import StyleTab from './StyleTab'
import { isDirty, useStore, type Config } from './store'
import { TAB_KEYS, initialTab, isTabKey, type TabKey } from './tabs'
import { Toaster, notify } from './toaster'

interface Props {
  cfg: Config
  api: Api
}

export default function App({ cfg, api }: Props) {
  const [tab, setTab] = useState(() => initialTab(window.location.hash))
  const [portal, setPortal] = useState<HTMLDivElement | null>(null)
  const { state, actions } = useStore(cfg, api, notify)
  const dirty = isDirty(state)
  const tabProps = { cfg, draft: state.draft, actions }

  const labels: Record<TabKey, string> = {
    general: __('General', 'woocommerce-parcelas'),
    display: __('Display', 'woocommerce-parcelas'),
    style: __('Style', 'woocommerce-parcelas'),
  }

  const selectTab = (next: unknown) => {
    if (!isTabKey(next)) return
    setTab(next)
    // replaceState: refresh-safe and shareable, without a history entry or a scroll jump.
    window.history.replaceState(null, '', `#${next}`)
  }

  return (
    <PortalContainer.Provider value={portal}>
      {/* CONTRACT: tests/e2e/support/settings-page.js finds the screen by this class and reads these data-* attributes. */}
      <div
        className="installment-prices-admin tw:mt-4 tw:flex tw:max-w-(--screen-max-width) tw:flex-col tw:gap-5 tw:text-foreground"
        data-busy-save={String(state.busy)}
        data-dirty={String(dirty)}
      >
        <Tabs value={tab} onValueChange={selectTab} className="tw:gap-5">
          <TabsList aria-label={__('Settings sections', 'woocommerce-parcelas')}>
            {TAB_KEYS.map((key) => (
              <TabsTrigger key={key} value={key}>
                {labels[key]}
              </TabsTrigger>
            ))}
          </TabsList>
          <TabsContent value="general" className="tw:flex tw:flex-col tw:gap-5">
            <GeneralTab {...tabProps} />
          </TabsContent>
          <TabsContent value="display" className="tw:flex tw:flex-col tw:gap-5">
            <DisplayTab {...tabProps} />
          </TabsContent>
          <TabsContent value="style" className="tw:flex tw:flex-col tw:gap-5">
            <StyleTab {...tabProps} />
          </TabsContent>
        </Tabs>
        <div className="tw:flex tw:flex-wrap tw:items-center tw:gap-3">
          <Button disabled={state.busy} onClick={() => actions.save(state.draft)}>
            {state.busy && <Spinner />}
            {state.busy ? __('Saving…', 'woocommerce-parcelas') : __('Save settings', 'woocommerce-parcelas')}
          </Button>
          {dirty && <span className="tw:text-body tw:text-muted-foreground">{__('You have unsaved changes.', 'woocommerce-parcelas')}</span>}
        </div>
        <Toaster />
        {/* Out of flow: Base UI wraps each portal in a div, which must not become a flex item and take a gap. */}
        <div ref={setPortal} className="tw:absolute" />
      </div>
      <footer className="tw:mt-8 tw:border-t tw:border-solid tw:border-border-muted tw:pt-4 tw:text-body tw:text-muted-foreground">
        <p className="tw:my-(--paragraph-margin) tw:text-paragraph">
          <strong>{cfg.plugin.name}</strong> <span className="tw:text-faint-foreground">·</span>{' '}
          {sprintf(
            /* translators: %s: plugin version number. */
            __('Version %s', 'woocommerce-parcelas'),
            cfg.plugin.version
          )}{' '}
          <span className="tw:text-faint-foreground">·</span>{' '}
          <TextLink href={cfg.supportUrl} target="_blank" rel="noopener noreferrer">
            {__('Get support', 'woocommerce-parcelas')}
          </TextLink>
        </p>
        <p className="tw:my-(--paragraph-margin) tw:text-paragraph">
          {__('WooCommerce is a trademark of its respective owner. This plugin is not affiliated with or endorsed by WooCommerce.', 'woocommerce-parcelas')}
        </p>
      </footer>
    </PortalContainer.Provider>
  )
}
