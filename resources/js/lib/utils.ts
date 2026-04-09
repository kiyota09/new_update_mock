import type { Updater } from "@tanstack/vue-table"
import type { ClassValue } from "clsx"
import type { Ref } from "vue"
import { clsx } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

// Role-based utilities
export const ROLES = {
    HRM: 'HRM',
    SCM: 'SCM'
};

export const POSITIONS = {
    MANAGER: 'manager',
    STAFF: 'staff'
};

export function getDashboardPath(user) {
    const paths = {
        [ROLES.HRM]: {
            [POSITIONS.STAFF]: '/dashboard/hrm/employee',
            [POSITIONS.MANAGER]: '/dashboard/hrm/manager'
        },
        [ROLES.SCM]: {
            [POSITIONS.STAFF]: '/dashboard/scm/employee',
            [POSITIONS.MANAGER]: '/dashboard/scm/manager'
        }
    };
    
    return paths[user.role]?.[user.position] || '/dashboard';
}

export function hasPermission(user, requiredRole, requiredPosition = null) {
    if (!user) return false;
    if (user.role !== requiredRole) return false;
    if (requiredPosition && user.position !== requiredPosition) return false;
    return true;
}

export function valueUpdater<T extends Updater<any>>(updaterOrValue: T, ref: Ref) {
  ref.value
    = typeof updaterOrValue === "function"
      ? updaterOrValue(ref.value)
      : updaterOrValue
}
