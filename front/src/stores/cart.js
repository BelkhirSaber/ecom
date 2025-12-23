import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useAuthStore } from './auth';

const CART_STORAGE_KEY = 'ecom_cart';

export const useCartStore = defineStore('cart', () => {
  const items = ref([]);
  const loading = ref(false);

  const itemCount = computed(() => items.value.reduce((sum, item) => sum + item.quantity, 0));
  
  const totalAmount = computed(() => {
    return items.value.reduce((sum, item) => {
      const price = item.product.price || 0;
      return sum + (price * item.quantity);
    }, 0);
  });

  function loadFromLocalStorage() {
    try {
      const stored = localStorage.getItem(CART_STORAGE_KEY);
      if (stored) {
        items.value = JSON.parse(stored);
      }
    } catch (error) {
      console.error('Failed to load cart from localStorage:', error);
    }
  }

  function saveToLocalStorage() {
    try {
      localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(items.value));
    } catch (error) {
      console.error('Failed to save cart to localStorage:', error);
    }
  }

  function addItem(product, variant = null, quantity = 1) {
    const existingIndex = items.value.findIndex(item => {
      if (variant) {
        return item.product.id === product.id && item.variant?.id === variant.id;
      }
      return item.product.id === product.id && !item.variant;
    });

    if (existingIndex !== -1) {
      items.value[existingIndex].quantity += quantity;
    } else {
      items.value.push({
        id: `${product.id}-${variant?.id || 'simple'}-${Date.now()}`,
        product,
        variant,
        quantity
      });
    }

    saveToLocalStorage();
  }

  function updateQuantity(itemId, quantity) {
    const item = items.value.find(i => i.id === itemId);
    if (item) {
      if (quantity <= 0) {
        removeItem(itemId);
      } else {
        item.quantity = quantity;
        saveToLocalStorage();
      }
    }
  }

  function removeItem(itemId) {
    items.value = items.value.filter(i => i.id !== itemId);
    saveToLocalStorage();
  }

  function clearCart() {
    items.value = [];
    saveToLocalStorage();
  }

  async function syncWithBackend() {
    const authStore = useAuthStore();
    if (!authStore.isAuthenticated) return;

    loading.value = true;
    try {
      const response = await authStore.api.get('/cart');
      const serverCart = response.data.data || [];

      items.value.forEach(localItem => {
        const exists = serverCart.find(serverItem => 
          serverItem.product_id === localItem.product.id &&
          serverItem.variant_id === localItem.variant?.id
        );

        if (!exists) {
          authStore.api.post('/cart/items', {
            product_id: localItem.product.id,
            variant_id: localItem.variant?.id,
            quantity: localItem.quantity
          });
        }
      });

      const mergedItems = serverCart.map(serverItem => ({
        id: `${serverItem.product_id}-${serverItem.variant_id || 'simple'}-${Date.now()}`,
        product: serverItem.product,
        variant: serverItem.variant,
        quantity: serverItem.quantity
      }));

      items.value = mergedItems;
      saveToLocalStorage();
    } catch (error) {
      console.error('Failed to sync cart with backend:', error);
    } finally {
      loading.value = false;
    }
  }

  loadFromLocalStorage();

  return {
    items,
    loading,
    itemCount,
    totalAmount,
    addItem,
    updateQuantity,
    removeItem,
    clearCart,
    syncWithBackend
  };
});
