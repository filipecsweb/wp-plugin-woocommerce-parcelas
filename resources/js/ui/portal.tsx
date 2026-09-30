/**
 * Container that every portalled primitive (dialog, tooltip, toasts) mounts into.
 * WHY: a portal to <body> would leave the mount, and with it app.css's scoped reset.
 *
 * @since 2.0.0
 */
import { createContext, useContext } from 'react'

export const PortalContainer = createContext<HTMLElement | null>(null)
export const usePortalContainer = () => useContext(PortalContainer)
