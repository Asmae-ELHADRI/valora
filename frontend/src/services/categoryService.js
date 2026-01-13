import api from './api'

/**
 * Service pour gérer les catégories de services
 */
const categoryService = {
  /**
   * Récupère toutes les catégories avec options de tri et recherche
   * @param {Object} options - Options de requête
   * @param {string} options.search - Terme de recherche
   * @param {string} options.sort - Critère de tri ('popular' ou 'alphabetical')
   * @returns {Promise} Liste des catégories
   */
  getCategories(options = {}) {
    const params = new URLSearchParams()
    if (options.search) params.append('search', options.search)
    if (options.sort) params.append('sort', options.sort)
    
    return api.get(`/admin/categories${params.toString() ? '?' + params : ''}`)
  },

  /**
   * Crée une nouvelle catégorie de service
   * @param {Object} categoryData - Données de la catégorie
   * @param {string} categoryData.name - Nom de la catégorie
   * @param {string} categoryData.description - Description de la catégorie
   * @param {string} categoryData.icon - Icône de la catégorie (optionnel)
   * @returns {Promise} Catégorie créée
   */
  createCategory(categoryData) {
    return api.post('/admin/categories', categoryData)
  },

  /**
   * Met à jour une catégorie existante
   * @param {number} categoryId - ID de la catégorie
   * @param {Object} categoryData - Données à mettre à jour
   * @param {string} categoryData.name - Nom de la catégorie
   * @param {string} categoryData.description - Description de la catégorie
   * @param {string} categoryData.icon - Icône de la catégorie (optionnel)
   * @returns {Promise} Catégorie mise à jour
   */
  updateCategory(categoryId, categoryData) {
    return api.put(`/admin/categories/${categoryId}`, categoryData)
  },

  /**
   * Supprime une catégorie
   * @param {number} categoryId - ID de la catégorie à supprimer
   * @returns {Promise} Réponse de suppression
   */
  deleteCategory(categoryId) {
    return api.delete(`/admin/categories/${categoryId}`)
  }
}

export default categoryService
