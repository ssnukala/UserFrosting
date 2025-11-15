import { ref, computed } from 'vue'
import axios from 'axios'
import { useRegle } from '@regle/core'
import { Severity } from '@userfrosting/sprinkle-core/interfaces'
import { useAlertsStore, useTranslator } from '@userfrosting/sprinkle-core/stores'
import type { ApiResponse, ApiErrorResponse } from '@userfrosting/sprinkle-core/interfaces'
import type { ProfileEditRequest } from '../interfaces'
import { useRuleSchemaAdapter } from '@userfrosting/sprinkle-core/composables'

// Lazy load schema
let schemaPromise: Promise<any> | null = null
function loadSchema() {
    if (!schemaPromise) {
        schemaPromise = import('../../schema/requests/profile-settings.yaml')
    }
    return schemaPromise
}

/**
 * API Composable
 */
export function useUserProfileEditApi() {
    const apiLoading = ref<boolean>(false)
    const apiError = ref<ApiErrorResponse | null>(null)
    const formData = ref<ProfileEditRequest>({
        first_name: '',
        last_name: '',
        locale: ''
    })
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
        r$
    }
}
