<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Module\AdminUi;

use InstallmentPricesForWooCommerce\Foundation\Hooks\Action;

/**
 * One subclass works as either top-level menu or submenu purely via
 * parentSlug()'s return. CONTRACT: a null parentSlug() makes the page a
 * top-level menu instead of a submenu.
 *
 * @since 2.0.0
 */
abstract class AdminPage
{
    /**
     * @since 2.0.0
     */
    private const NOTICE_HOOKS = ['admin_notices', 'all_admin_notices', 'network_admin_notices', 'user_admin_notices'];

    /**
     * @since 2.0.0
     */
    private string $hookSuffix = '';

    /**
     * @since 2.0.0
     */
    abstract protected function slug(): string;

    /**
     * @since 2.0.0
     */
    abstract protected function pageTitle(): string;

    /**
     * @since 2.0.0
     */
    abstract protected function menuTitle(): string;

    /**
     * @since 2.0.0
     */
    abstract protected function renderBody(): void;

    /**
     * @since 2.0.0
     */
    protected function capability(): string
    {
        return 'manage_options';
    }

    /**
     * @since 2.0.0
     */
    protected function parentSlug(): ?string
    {
        return null;
    }

    /**
     * Silently ignored for submenus (add_submenu_page takes no icon).
     *
     * @since 2.0.0
     */
    protected function icon(): string
    {
        return 'dashicons-performance';
    }

    /**
     * @since 2.0.0
     */
    protected function position(): ?int
    {
        return null;
    }

    /**
     * Whether the page's own screen drops every admin notice, core's included.
     *
     * @since 2.0.0
     */
    protected function silencesNotices(): bool
    {
        return false;
    }

    /**
     * @since 2.0.0
     */
    #[Action('admin_menu')]
    public function register(): void
    {
        $parent = $this->parentSlug();

        if ($parent === null) {
            $this->hookSuffix = add_menu_page(
                $this->pageTitle(),
                $this->menuTitle(),
                $this->capability(),
                $this->slug(),
                [$this, 'render'],
                $this->icon(),
                $this->position()
            );
        } else {
            $hookSuffix = add_submenu_page(
                $parent,
                $this->pageTitle(),
                $this->menuTitle(),
                $this->capability(),
                $this->slug(),
                [$this, 'render'],
                $this->position()
            );

            $this->hookSuffix = is_string($hookSuffix) ? $hookSuffix : '';
        }

        // WHY manual: the hook name embeds the runtime hook suffix, which a compile-time
        // #[Action] attribute can't express.
        if ($this->hookSuffix !== '' && $this->silencesNotices()) {
            add_action('load-' . $this->hookSuffix, [$this, 'silenceNotices']);
        }
    }

    /**
     * WHY in_admin_header, last: it fires after every plugin has queued its notices and
     * right before core prints them, so none reaches the screen, not even for a frame.
     *
     * @since 2.0.0
     */
    public function silenceNotices(): void
    {
        // WHY manual: it exists only once the page's own screen loads, a runtime
        // condition a compile-time #[Action] attribute can't express.
        add_action('in_admin_header', static function (): void {
            foreach (self::NOTICE_HOOKS as $hook) {
                remove_all_actions($hook);
            }
        }, PHP_INT_MAX);
    }

    /**
     * @since 2.0.0
     */
    public function render(): void
    {
        if (! current_user_can($this->capability())) {
            wp_die(esc_html($this->accessDeniedMessage()));
        }

        $this->renderBody();
    }

    /**
     * WHY untranslated: this kernel module stays slug-agnostic, so it carries no
     * gettext call of its own. Concrete pages override this to return the message
     * translated under their plugin's text domain.
     *
     * @since 2.0.0
     */
    protected function accessDeniedMessage(): string
    {
        return 'Sorry, you are not allowed to access this page.';
    }

    /**
     * Empty until register() runs on admin_menu.
     *
     * @since 2.0.0
     */
    public function hookSuffix(): string
    {
        return $this->hookSuffix;
    }

    /**
     * Built from the slug (not the menu registration), so it's valid before
     * register() runs on admin_menu. A parent that isn't a file (e.g. "woocommerce")
     * is a top-level menu slug, whose submenu pages live on admin.php.
     *
     * @since 2.0.0
     */
    public function url(): string
    {
        $parent = $this->parentSlug();
        $base   = $parent !== null && str_ends_with(explode('?', $parent, 2)[0], '.php') ? $parent : 'admin.php';

        return admin_url(add_query_arg('page', $this->slug(), $base));
    }
}
