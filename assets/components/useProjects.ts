import { ref, computed, onMounted } from 'vue'
import type { Project, ProjectFilter, ApiResponse } from '../types'

/**
 * Composable for managing project-related state and operations
 * 
 * @returns {Object} Project management utilities and reactive state
 */
export function useProjects() {
  /**
   * Reactive list of projects
   * @type {import('vue').Ref<Project[]>}
   */
  const projects = ref<Project[]>([])

  /**
   * Loading state indicator
   * @type {import('vue').Ref<boolean>}
   */
  const loading = ref(true)

  /**
   * Current page number for pagination
   * @type {import('vue').Ref<number>}
   */
  const currentPage = ref(1)

  /**
   * Number of items per page
   * @type {import('vue').Ref<number>}
   */
  const perPage = ref(25)

  /**
   * Total number of items
   * @type {import('vue').Ref<number>}
   */
  const totalItems = ref(0)

  /**
   * Reactive filter settings
   * @type {import('vue').Ref<ProjectFilter>}
   */
  const filters = ref<ProjectFilter>({
    category: '',
    currency: '',
    sortBy: 'published_at',
    sortOrder: 'desc',
    searchCategory: ''
  })

  /**
   * Fetches projects from the API based on current filters and pagination
   * 
   * @async
   * @throws {Error} When API request fails
   */
  const fetchProjects = async () => {
    try {
      loading.value = true
      const response = await fetch('/api/projects?' + new URLSearchParams({
        category: filters.value.searchCategory,
        currency: filters.value.currency,
        sortBy: filters.value.sortBy,
        sortOrder: filters.value.sortOrder,
        searchCategory: filters.value.searchCategory,
        page: currentPage.value.toString(),
        perPage: perPage.value.toString()
      }))
      const data = await response.json() as ApiResponse & { pagination?: { total: number } }
      if (data.success && Array.isArray(data.data)) {
        projects.value = data.data
        totalItems.value = data.pagination?.total ?? 0
      } else {
        console.error('Unexpected API response format:', data)
        projects.value = []
      }
    } catch (error) {
      console.error('Error fetching projects:', error)
      projects.value = []
    } finally {
      loading.value = false
    }
  }

  /**
   * Computed property for total number of pages
   * @type {import('vue').ComputedRef<number>}
   */
  const totalPages = computed(() => Math.ceil(totalItems.value / perPage.value))

  /**
   * Changes current page and fetches new data
   * 
   * @param {number} page - Page number to navigate to
   */
  const changePage = (page: number) => {
    currentPage.value = page
    fetchProjects()
  }

  /**
   * Updates filters and resets pagination
   * 
   * @param {Partial<ProjectFilter>} newFilters - New filter values to apply
   */
  const updateFilters = (newFilters: Partial<ProjectFilter>) => {
    filters.value = { ...filters.value, ...newFilters }
    currentPage.value = 1
    fetchProjects()
  }

  /**
   * Formats employer name from project data
   * 
   * @param {Project} project - Project object containing employer information
   * @returns {string} Formatted employer name
   */
  const getEmployerName = (project: Project): string => {
    if (!project.employer) return 'Unknown'
    const firstName = project.employer.first_name || ''
    const login = project.employer.login || 'Unknown'
    return firstName ? `${firstName} (${login})` : login
  }

  /**
   * Formats project budget information
   * 
   * @param {Project} project - Project object containing budget information
   * @returns {string} Formatted budget string
   */
  const formatBudget = (project: Project): string => {
    if (!project.budget_amount || !project.budget_currency) return 'Not specified'
    return `${project.budget_amount} ${project.budget_currency}`
  }

  /**
   * Formats project skills into an array
   * 
   * @param {Project} project - Project object containing skills information
   * @returns {string[]} Array of skill names
   */
  const formatSkills = (project: Project): string[] => {
    if (!project.skills) return ['Not specified']
    return project.skills.split(',').map(skill => skill.trim())
  }

  /**
   * Formats date string to localized date
   * 
   * @param {string} dateString - ISO date string
   * @returns {string} Formatted date string
   */
  const formatDate = (dateString: string): string => {
    try {
      return new Date(dateString).toLocaleDateString()
    } catch {
      return 'Invalid date'
    }
  }

  /**
   * Calculates row number based on current page and index
   * 
   * @param {number} index - Current row index
   * @returns {number} Calculated row number
   */
  const getRowNumber = (index: number): number => {
    return (currentPage.value - 1) * perPage.value + index + 1
  }

  /**
   * Filters projects by specific skill
   * 
   * @param {string} skill - Skill to filter by
   */
  const filterBySkill = (skill: string) => {
    filters.value.searchCategory = skill
    currentPage.value = 1
    fetchProjects()
  }

  /**
   * Resets all filters to default values
   */
  const resetFilters = () => {
    filters.value = {
      category: '',
      currency: '',
      sortBy: 'published_at',
      sortOrder: 'desc',
      searchCategory: ''
    }
    currentPage.value = 1
    fetchProjects()
  }

  /**
   * Clears API cache and reloads data
   * 
   * @async
   * @throws {Error} When cache clearing request fails
   */
  const clearCache = async () => {
    try {
      const response = await fetch('/api/clear-cache');
      const data = await response.json();
      if (data.success) {
        fetchProjects();
      }
    } catch (error) {
      console.error('Error clearing cache:', error);
    }
  }

  /**
   * Fetch projects when component is mounted
   */
  onMounted(() => {
    fetchProjects()
  })

  return {
    projects,
    loading,
    currentPage,
    perPage,
    totalItems,
    filters,
    totalPages,
    changePage,
    updateFilters,
    getEmployerName,
    formatBudget,
    formatSkills,
    formatDate,
    getRowNumber,
    filterBySkill,
    resetFilters,
    clearCache
  }
}