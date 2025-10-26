<template>
  <div class="search-container">
    <form @submit.prevent="performSearch" class="mb-8">
      <div class="flex gap-4 max-w-2xl">
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="placeholder"
          class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-mobility-red"
          minlength="2"
          required
        />
        <button
          type="submit"
          class="btn-mobility-primary"
          :disabled="loading"
        >
          {{ loading ? 'Suche läuft...' : 'Suchen' }}
        </button>
      </div>
    </form>

    <div v-if="hasSearched && !loading" class="search-results">
      <div v-if="results.length > 0">
        <div
          v-for="result in paginatedResults"
          :key="result.identifier"
          class="search-result-item mb-6 p-4 border-b border-gray-200"
        >
          <h2 class="text-xl font-semibold mb-2">
            <a :href="result.uri" class="mobility-link">
              {{ result.title }}
            </a>
          </h2>
          <p class="text-gray-600 text-sm mb-2">{{ result.uri }}</p>
          <p v-if="result.teaser" class="text-gray-700">
            {{ truncate(result.teaser, 150) }}
          </p>
        </div>

        <div v-if="totalPages > 1" class="pagination mt-8 flex justify-center gap-2">
          <button
            @click="currentPage = 1"
            :disabled="currentPage === 1"
            class="px-3 py-1 border rounded hover:bg-gray-100 disabled:opacity-50"
          >
            &laquo;
          </button>
          <button
            @click="currentPage--"
            :disabled="currentPage === 1"
            class="px-3 py-1 border rounded hover:bg-gray-100 disabled:opacity-50"
          >
            &lsaquo;
          </button>

          <span class="px-3 py-1">
            Seite {{ currentPage }} von {{ totalPages }}
          </span>

          <button
            @click="currentPage++"
            :disabled="currentPage === totalPages"
            class="px-3 py-1 border rounded hover:bg-gray-100 disabled:opacity-50"
          >
            &rsaquo;
          </button>
          <button
            @click="currentPage = totalPages"
            :disabled="currentPage === totalPages"
            class="px-3 py-1 border rounded hover:bg-gray-100 disabled:opacity-50"
          >
            &raquo;
          </button>
        </div>
      </div>

      <div v-else class="no-results text-center py-8 text-gray-600">
        {{ noResultsMessage }}
      </div>
    </div>

    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-mobility-red"></div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'

export default {
  name: 'Search',
  props: {
    placeholder: {
      type: String,
      default: 'Suche...'
    },
    noResultsMessage: {
      type: String,
      default: 'Keine Ergebnisse gefunden.'
    },
    searchTerm: {
      type: String,
      default: ''
    },
    resultsPerPage: {
      type: Number,
      default: 10
    }
  },
  setup(props) {
    const searchQuery = ref(props.searchTerm)
    const results = ref([])
    const loading = ref(false)
    const hasSearched = ref(false)
    const currentPage = ref(1)

    const totalPages = computed(() =>
      Math.ceil(results.value.length / props.resultsPerPage)
    )

    const paginatedResults = computed(() => {
      const start = (currentPage.value - 1) * props.resultsPerPage
      const end = start + props.resultsPerPage
      return results.value.slice(start, end)
    })

    const truncate = (text, length) => {
      if (!text || text.length <= length) return text
      return text.substring(0, length) + '...'
    }

    const performSearch = async () => {
      if (searchQuery.value.length < 2) return

      loading.value = true
      hasSearched.value = true
      currentPage.value = 1

      try {
        // Call our search API
        const response = await fetch(`/api/search?q=${encodeURIComponent(searchQuery.value)}`)
        if (response.ok) {
          const data = await response.json()
          results.value = data.results || []
        } else {
          // If API fails, show empty results
          console.warn('Search API not available, showing empty results')
          results.value = []
        }
      } catch (error) {
        console.error('Search error:', error)
        // If there's an error, we'll show a message but not break the page
        results.value = []
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      if (searchQuery.value) {
        performSearch()
      }
    })

    return {
      searchQuery,
      results,
      loading,
      hasSearched,
      currentPage,
      totalPages,
      paginatedResults,
      truncate,
      performSearch
    }
  }
}
</script>
