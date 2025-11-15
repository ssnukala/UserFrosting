import { ref, computed } from 'vue'
import axios from 'axios'
import { useRegle } from '@regle/core'
import { Severity } from '@userfrosting/sprinkle-core/interfaces'
import type { ApiResponse, AlertInterface } from '@userfrosting/sprinkle-core/interfaces'
import type { EmailEditRequest } from '../interfaces'
import { useAlertsStore } from '@userfrosting/sprinkle-core/stores'
import { useRuleSchemaAdapter } from '@userfrosting/sprinkle-core/composables'

// Lazy load schema
let schemaPromise: Promise<any> | null = null
function loadSchema() {
    if (!schemaPromise) {
        schemaPromise = import('../../schema/requests/account-email.yaml')
    }
    return schemaPromise
}

/**
 * API Composable
 */
export function useUserEmailEditApi() {
    const apiLoading = ref<boolean>(false)
    const apiError = ref<AlertInterface | null>(null)
    const formData = ref<EmailEditRequest>({
        email: '',
        passwordcheck: ''
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
