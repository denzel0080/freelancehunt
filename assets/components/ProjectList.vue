<script setup lang="ts">
import { useProjects } from './useProjects';

const {
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
  resetFilters
} = useProjects()
</script>

<template>
  <div class="space-y-4">
    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input 
          v-model="filters.searchCategory" 
          type="text" 
          placeholder="Search category (e.g. PHP)"
          class="rounded-md border-gray-300 shadow-sm"
          @input="updateFilters({ searchCategory: $event.target.value })" 
        />

        <select 
          v-model="filters.currency" 
          class="rounded-md border-gray-300 shadow-sm"
          @change="updateFilters({ currency: $event.target.value })"
        >
          <option value="">All Currencies</option>
          <option value="UAH">UAH</option>
          <option value="USD">USD</option>
          <option value="EUR">EUR</option>
          <option value="PLN">PLN</option>
        </select>

        <select 
          v-model="filters.sortBy" 
          class="rounded-md border-gray-300 shadow-sm"
          @change="updateFilters({ sortBy: $event.target.value })"
        >
          <option value="published_at">Date Published</option>
          <option value="budget_amount">Budget</option>
          <option value="name">Name</option>
        </select>

        <select 
          v-model="filters.sortOrder" 
          class="rounded-md border-gray-300 shadow-sm"
          @change="updateFilters({ sortOrder: $event.target.value })"
        >
          <option value="desc">Descending</option>
          <option value="asc">Ascending</option>
        </select>

        <button 
          @click="resetFilters"
          class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors"
        >
          Reset Filters
        </button>
      </div>
    </div>

    <!-- Projects Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Skills</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Budget</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Published</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="loading">
            <td colspan="6" class="px-6 py-4 text-center">Loading...</td>
          </tr>
          <template v-else>
            <tr v-for="(project, index) in projects" :key="project.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                {{ getRowNumber(index) }}
              </td>
              <td class="px-6 py-4">
                <a 
                  :href="`https://freelancehunt.com/project/${project.id}/${project.alias}.html`" 
                  target="_blank"
                  class="text-blue-600 hover:text-blue-800"
                >
                  {{ project.name }}
                </a>
              </td>
              <td class="px-6 py-4">
                <template v-for="(skill, skillIndex) in formatSkills(project)" :key="skillIndex">
                  <a href="#" @click.prevent="filterBySkill(skill)" class="text-green-600 hover:text-green-800">
                    {{ skill }}
                  </a>
                  <template v-if="skillIndex < formatSkills(project).length - 1">, </template>
                </template>
              </td>
              <td class="px-6 py-4">
                {{ formatBudget(project) }}
              </td>
              <td class="px-6 py-4">
                {{ getEmployerName(project) }}
              </td>
              <td class="px-6 py-4">
                {{ formatDate(project.published_at) }}
              </td>
            </tr>
            <tr v-if="projects.length === 0">
              <td colspan="6" class="px-6 py-4 text-center">No projects found</td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
      <div class="flex-1 flex justify-between sm:hidden">
        <button
          @click="changePage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          :class="{ 'opacity-50 cursor-not-allowed': currentPage === 1 }"
        >
          Previous
        </button>
        <button
          @click="changePage(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          :class="{ 'opacity-50 cursor-not-allowed': currentPage === totalPages }"
        >
          Next
        </button>
      </div>
      <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
          <p class="text-sm text-gray-700">
            Showing
            <span class="font-medium">{{ ((currentPage - 1) * perPage) + 1 }}</span>
            to
            <span class="font-medium">{{ Math.min(currentPage * perPage, totalItems) }}</span>
            of
            <span class="font-medium">{{ totalItems }}</span>
            results
          </p>
        </div>
        <div>
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <button
              @click="changePage(currentPage - 1)"
              :disabled="currentPage === 1"
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
              :class="{ 'opacity-50 cursor-not-allowed': currentPage === 1 }"
            >
              Previous
            </button>
            
            <template v-for="page in totalPages" :key="page">
              <button
                v-if="page === currentPage || 
                      page === 1 || 
                      page === totalPages || 
                      (page >= currentPage - 1 && page <= currentPage + 1)"
                @click="changePage(page)"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium"
                :class="currentPage === page ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'text-gray-500 hover:bg-gray-50'"
              >
                {{ page }}
              </button>
              <span
                v-else-if="page === currentPage - 2 || page === currentPage + 2"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
              >
                ...
              </span>
            </template>

            <button
              @click="changePage(currentPage + 1)"
              :disabled="currentPage === totalPages"
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
              :class="{ 'opacity-50 cursor-not-allowed': currentPage === totalPages }"
            >
              Next
            </button>
          </nav>

          <div class="flex items-center space-x-2">
            <input
              type="number"
              :value="currentPage"
              @input="(e) => {
                const page = parseInt(e.target.value);
                if (page >= 1 && page <= totalPages) {
                  changePage(page);
                }
              }"
              class="w-16 h-8 px-2 border border-gray-300 rounded-md text-sm"
              :min="1"
              :max="totalPages"
              aria-label="Go to page"
            />
            <span class="text-sm text-gray-500">of {{ totalPages }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>