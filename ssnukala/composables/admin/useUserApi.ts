import { ref, toValue, computed } from 'vue'
import axios from 'axios'
import { useRegle } from '@regle/core'
import { Severity, type ApiErrorResponse } from '@userfrosting/sprinkle-core/interfaces'
import type {
    UserCreateRequest,
    UserCreateResponse,
    UserDeleteResponse,
    UserEditRequest,
    UserEditResponse,
    UserResponse
} from '../interfaces'
import { useRuleSchemaAdapter } from '@userfrosting/sprinkle-core/composables'
import { useAlertsStore } from '@userfrosting/sprinkle-core/stores'

// Lazy load schema
let schemaPromise: Promise<any> | null = null
function loadSchema() {
    if (!schemaPromise) {
        schemaPromise = import('../../schema/requests/user/create.yaml')
    }
    return schemaPromise
}

/**
 * Vue composable for User CRUD operations.
 */
export function useUserApi() {
    const defaultFormData = (): UserCreateRequest => ({
        user_name: '',
        group_id: 0,
        first_name: '',
        last_name: '',
        email: '',
        locale: 'users'
    })

    const apiLoading = ref<boolean>(false)
    const apiError = ref<ApiErrorResponse | null>()
    const formData = ref<UserCreateRequest>(defaultFormData())
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
        defaultFormData
    }
}
