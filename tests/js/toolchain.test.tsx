import { render, screen } from '@testing-library/react'
import { version } from 'react'
import { expect, test } from 'vitest'

test('renders JSX with the React version core ships', () => {
  render(<p>ready</p>)

  expect(screen.getByText('ready').tagName).toBe('P')
  expect(version).toBe('18.3.1')
})
