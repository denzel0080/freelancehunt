/**
 * Represents an employer in the system
 * @interface Employer
 */
export interface Employer {
    /** Unique username/login identifier of the employer */
    login: string
    /** First name of the employer, may be null if not provided */
    first_name: string | null
    /** Last name of the employer, may be null if not provided */
    last_name: string | null
  }
  
  /**
   * Represents a project in the system
   * @interface Project
   */
  export interface Project {
    /** Unique numerical identifier of the project */
    id: number
    /** Full name/title of the project */
    name: string
    /** URL-friendly version of the project name */
    alias: string
    /** Budget amount for the project, null if not specified */
    budget_amount: number | null
    /** Currency code for the budget (e.g., 'USD', 'EUR'), null if not specified */
    budget_currency: string | null
    /** ISO timestamp when the project was published */
    published_at: string
    /** Information about the employer who posted the project, null if anonymous */
    employer: Employer | null
    /** Comma-separated list of required skills for the project */
    skills?: string
  }
  
  /**
   * Filter criteria for project queries
   * @interface ProjectFilter
   */
  export interface ProjectFilter {
    /** Category filter for projects */
    category: string
    /** Currency filter for project budgets */
    currency: string
    /** Field to sort results by */
    sortBy: string
    /** Sort direction ('asc' or 'desc') */
    sortOrder: string
    /** Search term for filtering by category */
    searchCategory: string
  }
  
  /**
   * Pagination information for API responses
   * @interface PaginationData
   */
  export interface PaginationData {
    /** Total number of items available */
    total: number
    /** Current page number */
    currentPage: number
    /** Number of items per page */
    perPage: number
    /** Number of the last available page */
    lastPage: number
  }
  
  /**
   * Standard API response format for project queries
   * @interface ApiResponse
   */
  export interface ApiResponse {
    /** Indicates if the API request was successful */
    success: boolean
    /** Array of projects returned by the query */
    data: Project[]
    /** Error message if the request failed */
    error?: string
    /** Pagination information for the response */
    pagination: PaginationData
  }
  
  /**
   * Query parameters for project API requests
   * @interface ProjectQueryParams
   */
  export interface ProjectQueryParams {
    /** Filter by project category */
    category?: string
    /** Filter by budget currency */
    currency?: string
    /** Field to sort results by */
    sortBy?: string
    /** Sort direction ('asc' or 'desc') */
    sortOrder?: string
    /** Search term for filtering by category */
    searchCategory?: string
    /** Page number for pagination */
    page?: number
    /** Number of items per page */
    perPage?: number
  }