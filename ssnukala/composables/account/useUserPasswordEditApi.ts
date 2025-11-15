import { ref, computed } from 'vue'
import axios from 'axios'
import { useRegle } from '@regle/core'
import {
    type ApiResponse,
    type ApiErrorResponse,
    Severity
} from '@userfrosting/sprinkle-core/interfaces'
import type { PasswordEditRequest } from '../interfaces'
import { useAlertsStore, useConfigStore } from '@userfrosting/sprinkle-core/stores'
import { useRuleSchemaAdapter } from '@userfrosting/sprinkle-core/composables'

// Lazy load schema
let schemaPromise: Promise<any> | null = null
function loadSchema() {
    if (!schemaPromise) {
        schemaPromise = import('../../schema/requests/account-settings.yaml')
    }
    return schemaPromise
}

/**
 * API Composable
 */
export function useUserPasswordEditApi() {
    const apiLoading = ref<boolean>(false)
    const apiError = ref<ApiErrorResponse | null>(null)
    const passwordMinLength = ref<number>(0)
    const passwordMaxLength = ref<number>(0)
    const formData = ref<PasswordEditRequest>({
        passwordcheck: '',
        password: '',
        passwordc: ''
    })
    const schemaLoaded = ref<boolean>(false)
    const schemaData = ref<any>({})

    // Retrieve min/max password length from site settings and update validator
    // constraints
    const config = useConfigStore()
    passwordMinLength.value = config.get('site.password.length.min')
    passwordMaxLength.value = config.get('site.password.length.max')

    // TODO : Pass min/max to Regle (can't change defined regle, the message won't follow)
    // TODO : matches rules is not implemented

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
        passwordMinLength,
        passwordMaxLength
    }
}
