import { ref, toValue, watch, computed } from 'vue'
import axios from 'axios'
import { useRegle } from '@regle/core'
import slug from 'limax'
import { Severity, type ApiErrorResponse } from '@userfrosting/sprinkle-core/interfaces'
import type {
    RoleCreateResponse,
    RoleCreateRequest,
    RoleEditRequest,
    RoleEditResponse,
    RoleDeleteResponse,
    RoleResponse
} from '../interfaces'
import { useAlertsStore } from '@userfrosting/sprinkle-core/stores'
import { useRuleSchemaAdapter } from '@userfrosting/sprinkle-core/composables'

// Lazy load schema
let schemaPromise: Promise<any> | null = null
function loadSchema() {
    if (!schemaPromise) {
        schemaPromise = import('../../schema/requests/role.yaml')
    }
    return schemaPromise
}

/**
 * Vue composable for Role CRUD operations.
 */
export function useRoleApi() {
    const defaultFormData = (): RoleCreateRequest => ({
        name: '',
        slug: '',
        description: ''
    })

    const slugLocked = ref<boolean>(true)
    const apiLoading = ref<boolean>(false)
    const apiError = ref<ApiErrorResponse | null>(null)
    const formData = ref<RoleCreateRequest>(defaultFormData())
    const schemaLoaded = ref<boolean>(false)
    const schemaData = ref<any>({})

    // Load the schema lazily
    loadSchema().then((module) => {
        schemaData.value = module.default
        schemaLoaded.value = true
    })

    // Load the schema and set up the validator
    const { r$ } = useRegle(formData, computed(() => schemaLoaded.value ? useRuleSchemaAdapter().adapt(schemaData.value) : {}))

    // Additional methods would be here...
    return {
        apiLoading,
        apiError,
        formData,
        r$,
        slugLocked,
        defaultFormData
    }
}
