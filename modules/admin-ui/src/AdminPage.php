<?php

declare(strict_types=1);

namespace InstallmentPricesForWooCommerce\Module\AdminUi;

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
     * @since 2.0.0
     */
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

            return;
        }

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
        $base   = $parent !== null && str_ends_with($parent, '.php') ? $parent : 'admin.php';

        return admin_url(add_query_arg('page', $this->slug(), $base));
    }
}
