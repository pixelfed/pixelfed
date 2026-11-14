import { inject, type ComputedRef, type InjectionKey } from 'vue'

export type DropdownPresentation = 'menu' | 'modal'

export interface DropdownContext {
  presentation: ComputedRef<DropdownPresentation>
  close: () => void
}

export const dropdownKey: InjectionKey<DropdownContext> = Symbol('dropdown')

export function useDropdown(): DropdownContext | null {
  return inject(dropdownKey, null)
}
