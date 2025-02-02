import { ref, computed, onMounted } from 'vue'
import type { Project, ProjectFilter, ApiResponse } from '../types'

export function useProjects() {
  const projects = ref<Project[]>([])
  const loading = ref(true)
  const currentPage = ref(1)
  const perPage = ref(25)
  const totalItems = ref(0)
  
  const filters = ref<ProjectFilter>({
    category: '',
    currency: '',
    sortBy: 'published_at',
    sortOrder: 'desc',
    searchCategory: ''
  })

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

  const totalPages = computed(() => Math.ceil(totalItems.value / perPage.value))

  const changePage = (page: number) => {
    currentPage.value = page
    fetchProjects()
  }

  const updateFilters = (newFilters: Partial<ProjectFilter>) => {
    filters.value = { ...filters.value, ...newFilters }
    currentPage.value = 1
    fetchProjects()
  }

  const getEmployerName = (project: Project): string => {
    if (!project.employer) return 'Unknown'
    const firstName = project.employer.first_name || ''
    const login = project.employer.login || 'Unknown'
    return firstName ? `${firstName} (${login})` : login
  }

  const formatBudget = (project: Project): string => {
    if (!project.budget_amount || !project.budget_currency) return 'Not specified'
    return `${project.budget_amount} ${project.budget_currency}`
  }

  const formatSkills = (project: Project): string[] => {
    if (!project.skills) return ['Not specified']
    return project.skills.split(',').map(skill => skill.trim())
  }

  const formatDate = (dateString: string): string => {
    try {
      return new Date(dateString).toLocaleDateString()
    } catch {
      return 'Invalid date'
    }
  }

  const getRowNumber = (index: number): number => {
    return (currentPage.value - 1) * perPage.value + index + 1
  }

  const filterBySkill = (skill: string) => {
    filters.value.searchCategory = skill
    currentPage.value = 1
    fetchProjects()
  }

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