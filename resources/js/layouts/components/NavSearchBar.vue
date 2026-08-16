<script setup>
import Shepherd from 'shepherd.js'
import { withQuery } from 'ufo'
import { useConfigStore } from '@core/stores/config'

defineOptions({
  inheritAttrs: false,
})

const configStore = useConfigStore()
const isAppSearchBarVisible = ref(false)

const suggestionGroups = ref([])
const noDataSuggestions = ref([])
const allNavItems = ref([])
const searchResult = ref([])
const searchQuery = ref('')
const isLoading = ref(false)
const router = useRouter()

onMounted(async () => {
  try {
    const { data } = await useApi('/apps/modules/navigation')
    const rawNav = data.value || []
    
    let flattened = []

    const flatten = (items, parentGroup = 'Modules') => {
      items.forEach(item => {
        if (item.heading) {
          parentGroup = item.heading
        } else if (item.children) {
          flatten(item.children, item.title)
        } else if (item.to || item.url) {
          flattened.push({
            group: parentGroup,
            title: item.title,
            icon: item.icon?.icon || 'ri-circle-line',
            url: typeof item.to === 'string' ? { name: item.to } : (item.to || item.url),
          })
        }
      })
    }
    
    flatten(rawNav)
    allNavItems.value = flattened
    
    let grouped = {}
    flattened.forEach(item => {
      if(!grouped[item.group]) grouped[item.group] = []
      grouped[item.group].push(item)
    })
    
    suggestionGroups.value = Object.keys(grouped).map(k => ({
      title: k,
      content: grouped[k],
    }))
    
    noDataSuggestions.value = flattened.slice(0, 3)
  } catch (e) {
    console.error('Failed to fetch navigation for search bar', e)
  }
})

const fetchResults = () => {
  isLoading.value = true
  
  if (!searchQuery.value) {
    searchResult.value = []
    isLoading.value = false
    
    return
  }
  
  const q = searchQuery.value.toLowerCase()
  const matched = allNavItems.value.filter(item => item.title.toLowerCase().includes(q))
  
  let groupedResult = {}
  matched.forEach(item => {
    if(!groupedResult[item.group]) groupedResult[item.group] = { title: item.group, children: [] }
    groupedResult[item.group].children.push(item)
  })
  
  searchResult.value = Object.values(groupedResult)
  isLoading.value = false
}

watch(searchQuery, fetchResults)

const redirectToSuggestedOrSearchedPage = selected => {
  router.push(selected.url)
  isAppSearchBarVisible.value = false
  searchQuery.value = ''
}

const LazyAppBarSearch = defineAsyncComponent(() => import('@core/components/AppBarSearch.vue'))
</script>

<template>
  <div
    class="d-flex align-center cursor-pointer gap-x-2"
    v-bind="$attrs"
    style="user-select: none;"
    @click="isAppSearchBarVisible = !isAppSearchBarVisible"
  >
    <!-- 👉 Search Trigger button -->
    <!-- close active tour while opening search bar using icon -->
    <IconBtn @click="Shepherd.activeTour?.cancel()">
      <VIcon icon="ri-search-line" />
    </IconBtn>

    <div
      v-if="configStore.appContentLayoutNav === 'vertical'"
      class="d-none d-md-flex text-disabled text-body-1 gap-x-2"
      @click="Shepherd.activeTour?.cancel()"
    >
      <div>
        Search
      </div>
      <div class="meta-key">
        &#8984;K
      </div>
    </div>
  </div>

  <!-- 👉 App Bar Search -->
  <LazyAppBarSearch
    v-model:is-dialog-visible="isAppSearchBarVisible"
    :search-results="searchResult"
    :is-loading="isLoading"
    @search="searchQuery = $event"
  >
    <!-- suggestion -->
    <template #suggestions>
      <VCardText class="app-bar-search-suggestions pa-12">
        <VRow v-if="suggestionGroups">
          <VCol
            v-for="suggestion in suggestionGroups"
            :key="suggestion.title"
            cols="12"
            sm="6"
          >
            <p class="custom-letter-spacing text-xs text-disabled text-uppercase py-2 px-4 mb-0">
              {{ $t(suggestion.title) }}
            </p>
            <VList class="card-list">
              <VListItem
                v-for="item in suggestion.content"
                :key="item.title"
                link
                class="app-bar-search-suggestion mx-4 mt-2"
                @click="redirectToSuggestedOrSearchedPage(item)"
              >
                <VListItemTitle>{{ $t(item.title) }}</VListItemTitle>
                <template #prepend>
                  <VIcon
                    :icon="item.icon"
                    size="20"
                    class="me-n1"
                  />
                </template>
              </VListItem>
            </VList>
          </VCol>
        </VRow>
      </VCardText>
    </template>

    <!-- no data suggestion -->
    <template #noDataSuggestion>
      <div class="mt-6">
        <div class="text-center text-disabled py-2">
          Try searching for
        </div>
        <h6
          v-for="suggestion in noDataSuggestions"
          :key="suggestion.title"
          class="app-bar-search-suggestion text-h6 font-weight-regular cursor-pointer py-2 px-4"
          @click="redirectToSuggestedOrSearchedPage(suggestion)"
        >
          <VIcon
            size="20"
            :icon="suggestion.icon"
            class="me-2"
          />
          <span class="d-inline-block">{{ $t(suggestion.title) }}</span>
        </h6>
      </div>
    </template>

    <!-- search result -->
    <template #searchResult="{ item }">
      <VListSubheader class="text-disabled custom-letter-spacing font-weight-regular ps-4">
        {{ $t(item.title) }}
      </VListSubheader>
      <VListItem
        v-for="list in item.children"
        :key="list.title"
        @click="redirectToSuggestedOrSearchedPage(list)"
      >
        <template #prepend>
          <VIcon
            size="20"
            :icon="list.icon"
            class="me-n1"
          />
        </template>
        <template #append>
          <VIcon
            size="20"
            icon="ri-corner-down-left-line"
            class="enter-icon text-medium-emphasis"
          />
        </template>
        <VListItemTitle>
          {{ $t(list.title) }}
        </VListItemTitle>
      </VListItem>
    </template>
  </LazyAppBarSearch>
</template>

<style lang="scss">
@use "@styles/variables/vuetify.scss";

.meta-key {
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 6px;
  block-size: 1.5625rem;
  padding-block: 0.1rem;
  padding-inline: 0.25rem;
}

.app-bar-search-dialog {
  .custom-letter-spacing {
    letter-spacing: 0.8px;
  }

  .card-list {
    --v-card-list-gap: 8px;
  }
}
</style>
