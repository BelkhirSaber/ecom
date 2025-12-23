<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { getMockImage } from '@/data/mockProducts';

const { t, locale } = useI18n();

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8001/api/v1',
  headers: { Accept: 'application/json' }
});

const currentSlide = ref(0);
const intervalMs = 5000;
let timer;

const heroData = ref([]);
const categoriesData = ref([]);
const sectionData = ref(null);
const featuredProductsData = ref([]);
const perksData = ref([]);
const topRatedData = ref([]);
const topRatedSectionData = ref(null);
const topPromotionsData = ref([]);
const topPromotionsSectionData = ref(null);
const newProductsData = ref([]);
const newProductsSectionData = ref(null);
const flashDeals = ref([
  {
    title: 'Offres du jour',
    subtitle: 'Sélection limitée, prix flash',
    badge: '-30%',
    cta: 'Voir les offres',
    image: getMockImage(8)
  }
]);

const collections = [
  { title: 'Maison & Déco', image: getMockImage(9) },
  { title: 'Accessoires tech', image: getMockImage(10) },
  { title: 'Beauté & Bien-être', image: getMockImage(11) },
  { title: 'Sport & outdoor', image: getMockImage(12) }
];

const popularProducts = [
  { title: 'Lunettes design', price: '49€', image: getMockImage(13) },
  { title: 'Diffuseur arôme', price: '59€', image: getMockImage(14) },
  { title: 'Tapis minimal', price: '89€', image: getMockImage(15) },
  { title: 'Sac weekend', price: '119€', image: getMockImage(16) }
];

const partnerSellers = [
  { name: 'Nova', logo: getMockImage(17) },
  { name: 'Atelier 9', logo: getMockImage(18) },
  { name: 'Studio M', logo: getMockImage(19) },
  { name: 'Crafted', logo: getMockImage(20) },
  { name: 'Kali', logo: getMockImage(21) },
  { name: 'Maison L', logo: getMockImage(22) }
];

const fallbackHeroSlides = computed(() => t('home.hero.slides', locale.value, { returnObjects: true }));
const heroSlides = computed(() => (heroData.value?.length ? heroData.value : fallbackHeroSlides.value));
const activeSlide = computed(() => heroSlides.value?.[currentSlide.value] ?? heroSlides.value?.[0] ?? null);

const fallbackCategories = computed(() => t('home.categories.items', locale.value, { returnObjects: true }));
const categories = computed(() => (categoriesData.value?.length ? categoriesData.value : fallbackCategories.value));
const categoryCards = computed(() =>
  (categories.value ?? []).map((cat, index) => ({
    label: typeof cat === 'string' ? cat : cat?.name ?? '',
    image: getMockImage(index),
    index
  }))
);

const fallbackSection = computed(() => t('home.section', locale.value, { returnObjects: true }));
const sectionCopy = computed(() => sectionData.value ?? fallbackSection.value);

const fallbackFeaturedProducts = computed(() => t('home.products.items', locale.value, { returnObjects: true }));
const featuredProducts = computed(() => (featuredProductsData.value?.length ? featuredProductsData.value : fallbackFeaturedProducts.value));
const pricePrefix = computed(() => t('home.products.pricePrefix'));

const fallbackPerks = computed(() => t('home.perks', locale.value, { returnObjects: true }));
const perks = computed(() => (perksData.value?.length ? perksData.value : fallbackPerks.value));

const fallbackTopRatedItems = computed(() => t('home.topRated.items', locale.value, { returnObjects: true }));
const topRatedSection = computed(() => topRatedSectionData.value ?? {
  title: t('home.topRated.title'),
  subtitle: t('home.topRated.subtitle')
});
const topRatedProducts = computed(() => (topRatedData.value?.length ? topRatedData.value : fallbackTopRatedItems.value ?? []));

const fallbackTopPromotionsItems = computed(() => t('home.topPromotions.items', locale.value, { returnObjects: true }));
const topPromotionsSection = computed(() => topPromotionsSectionData.value ?? {
  title: t('home.topPromotions.title'),
  subtitle: t('home.topPromotions.subtitle')
});
const topPromotions = computed(() => (topPromotionsData.value?.length ? topPromotionsData.value : fallbackTopPromotionsItems.value ?? []));

const fallbackNewProductsItems = computed(() => t('home.newProducts.items', locale.value, { returnObjects: true }));
const newProductsSection = computed(() => newProductsSectionData.value ?? {
  title: t('home.newProducts.title'),
  subtitle: t('home.newProducts.subtitle')
});
const newProducts = computed(() => (newProductsData.value?.length ? newProductsData.value : fallbackNewProductsItems.value ?? []));

const topRatedCta = computed(() => t('home.topRated.cta'));
const topPromotionsCta = computed(() => t('home.topPromotions.cta'));
const newProductsCta = computed(() => t('home.newProducts.cta'));

const goToSlide = (index) => {
  currentSlide.value = index;
};

const nextSlide = () => {
  if (!heroSlides.value?.length) {
    return;
  }
  currentSlide.value = (currentSlide.value + 1) % heroSlides.value.length;
};

const clearAutoplay = () => {
  if (timer) {
    clearInterval(timer);
    timer = undefined;
  }
};

const startAutoplay = () => {
  clearAutoplay();
  if (heroSlides.value?.length > 1) {
    timer = setInterval(nextSlide, intervalMs);
  }
};

async function fetchBlock(key) {
  try {
    const response = await api.get(`/blocks/${key}`, {
      params: { lang: locale.value }
    });
    return response.data?.data;
  } catch (error) {
    console.warn(`Bloc ${key} introuvable`, error?.response?.data ?? error?.message);
    return null;
  }
}

async function loadContent() {
  clearAutoplay();
  currentSlide.value = 0;

  try {
    const [
      heroBlock,
      categoriesBlock,
      featuredBlock,
      perksBlock,
      topRatedBlock,
      topPromotionsBlock,
      newBlock,
      promotionsResponse,
      newProductsResponse
    ] = await Promise.all([
      fetchBlock('home-hero'),
      fetchBlock('home-categories'),
      fetchBlock('home-featured-products'),
      fetchBlock('home-perks'),
      fetchBlock('home-top-rated'),
      fetchBlock('home-top-promotions'),
      fetchBlock('home-new-products'),
      api
        .get('/promotions', {
          params: {
            lang: locale.value,
            is_active: true,
            per_page: 6
          }
        })
        .catch(() => null),
      api
        .get('/products', {
          params: {
            only_active: true,
            per_page: 6,
            sort: 'latest'
          }
        })
        .catch(() => null)
    ]);

    heroData.value = heroBlock?.content?.slides ?? [];
    categoriesData.value = categoriesBlock?.content?.items ?? categoriesBlock?.content ?? [];
    sectionData.value = heroBlock?.content?.section ?? featuredBlock?.content?.section ?? (heroBlock?.title
      ? {
          title: heroBlock.title,
          subtitle: heroBlock?.content?.subtitle ?? heroBlock?.content?.description ?? null
        }
      : null);

    const blockProducts = featuredBlock?.content?.products ?? [];
    const promotionsProducts = promotionsResponse?.data?.data?.map((promotion) => ({
      title: promotion.name,
      price: promotion.discount_value,
      image: promotion.media?.cover ?? promotion.media?.images?.[0]?.url ?? null
    })) ?? [];

    featuredProductsData.value = blockProducts.length ? blockProducts : promotionsProducts;
    perksData.value = perksBlock?.content?.items ?? perksBlock?.content ?? [];

    const topRatedBlockItems = topRatedBlock?.content?.products ?? topRatedBlock?.content?.items ?? topRatedBlock?.content ?? [];
    topRatedData.value = topRatedBlockItems.map((item, index) => ({
      id: item.id ?? item.product_id ?? index,
      name: item.name ?? item.title,
      price: item.pricing?.price ?? item.price ?? item.discount_value ?? null,
      currency: item.pricing?.currency ?? item.currency ?? 'USD',
      description: item.short_description ?? item.description ?? '',
      image: item.media?.cover ?? item.image ?? getMockImage(index)
    }));
    topRatedSectionData.value = topRatedBlock?.content?.section ?? (topRatedBlock?.title
      ? {
          title: topRatedBlock.title,
          subtitle: topRatedBlock?.content?.subtitle ?? topRatedBlock?.description ?? null
        }
      : null);

    const promotionsList = topPromotionsBlock?.content?.items ?? topPromotionsBlock?.content ?? promotionsResponse?.data?.data ?? [];
    topPromotionsData.value = promotionsList.map((item, index) => ({
      id: item.product_id ?? item.id ?? index,
      name: item.name ?? item.title,
      description: item.description ?? item.short_description ?? '',
      badge: item.badge ?? (item.discount_value
        ? `-${item.discount_value}${item.currency ?? item.pricing?.currency ?? ''}`
        : item.percentage
          ? `-${item.percentage}%`
          : null),
      price: item.pricing?.price ?? item.price ?? item.discount_value ?? null,
      currency: item.pricing?.currency ?? item.currency ?? 'USD',
      image: item.media?.cover ?? item.image ?? item.banner ?? getMockImage(index + 3),
      slug: item.slug ?? null
    }));
    topPromotionsSectionData.value = topPromotionsBlock?.content?.section ?? (topPromotionsBlock?.title
      ? {
          title: topPromotionsBlock.title,
          subtitle: topPromotionsBlock?.content?.subtitle ?? topPromotionsBlock?.description ?? null
        }
      : null);

    const newBlockItems = newBlock?.content?.products ?? newBlock?.content?.items ?? newBlock?.content ?? [];
    const apiNewProducts = newProductsResponse?.data?.data ?? [];
    const combinedNewProducts = newBlockItems.length ? newBlockItems : apiNewProducts;
    newProductsData.value = combinedNewProducts.map((item, index) => ({
      id: item.id ?? item.product_id ?? index,
      name: item.name ?? item.title,
      price: item.pricing?.price ?? item.price ?? item.discount_value ?? null,
      currency: item.pricing?.currency ?? item.currency ?? 'USD',
      description: item.short_description ?? item.description ?? '',
      image: item.media?.cover ?? item.image ?? getMockImage(index + 6)
    }));
    newProductsSectionData.value = newBlock?.content?.section ?? (newBlock?.title
      ? {
          title: newBlock.title,
          subtitle: newBlock?.content?.subtitle ?? newBlock?.description ?? null
        }
      : null);

    // Fallbacks if API returns empty to avoid blank sections
    if (!heroData.value?.length) heroData.value = fallbackHeroSlides.value ?? [];
    if (!categoriesData.value?.length) categoriesData.value = fallbackCategories.value ?? [];
    if (!featuredProductsData.value?.length) featuredProductsData.value = fallbackFeaturedProducts.value ?? [];
    if (!perksData.value?.length) perksData.value = fallbackPerks.value ?? [];
    if (!topRatedData.value?.length) topRatedData.value = fallbackTopRatedItems.value ?? [];
    if (!topPromotionsData.value?.length) topPromotionsData.value = fallbackTopPromotionsItems.value ?? [];
    if (!newProductsData.value?.length) newProductsData.value = fallbackNewProductsItems.value ?? [];
    if (!topRatedSectionData.value) {
      topRatedSectionData.value = {
        title: t('home.topRated.title'),
        subtitle: t('home.topRated.subtitle')
      };
    }
    if (!topPromotionsSectionData.value) {
      topPromotionsSectionData.value = {
        title: t('home.topPromotions.title'),
        subtitle: t('home.topPromotions.subtitle')
      };
    }
    if (!newProductsSectionData.value) {
      newProductsSectionData.value = {
        title: t('home.newProducts.title'),
        subtitle: t('home.newProducts.subtitle')
      };
    }
  } catch (error) {
    console.warn('Home content loading failed, using fallbacks', error);
  } finally {
    startAutoplay();
  }
}

function resolveProductLink(item) {
  if (item?.id) {
    return { name: 'product', params: { id: item.id } };
  }
  if (item?.slug) {
    return { name: 'cms-page', params: { slug: item.slug } };
  }
  return { name: 'catalogue' };
}

function formattedPrice(item) {
  if (!item?.price) return null;
  const currency = item.currency ?? 'USD';
  return new Intl.NumberFormat(locale.value ?? 'fr-FR', {
    style: 'currency',
    currency
  }).format(Number(item.price));
}

watch(() => locale.value, () => {
  loadContent();
});

watch(heroSlides, (slides) => {
  if (!slides?.length) {
    clearAutoplay();
    return;
  }
  currentSlide.value = 0;
  startAutoplay();
});

onMounted(() => {
  loadContent();
  startAutoplay();
});

onUnmounted(() => {
  clearAutoplay();
});
</script>

<template>
  <section class="home">
    <div class="hero">
      <div class="hero-content">
        <Tag value="EF 2025" severity="warning" icon="pi pi-sparkles" class="hero-tag" />
        <p class="hero-subtitle">{{ activeSlide?.subtitle }}</p>
        <h1 class="hero-title">{{ activeSlide?.title }}</h1>
        <Button
          :label="activeSlide?.cta"
          class="hero-cta"
          icon="pi pi-arrow-right"
          iconPos="right"
          @click="$router.push({ name: 'catalogue' })"
        />
      </div>
      <div class="hero-visual">
        <div
          class="hero-image"
          :style="{ backgroundImage: activeSlide?.image ? `url(${activeSlide.image})` : undefined }"
        />
        <div class="hero-dots">
          <button
            v-for="(slide, index) in heroSlides"
            :key="slide.title + index"
            @click="goToSlide(index)"
            :class="['dot', { active: currentSlide === index }]"
            :aria-label="`Afficher ${slide.title}`"
          />
        </div>
      </div>
    </div>

    <section class="category-grid" aria-labelledby="category-heading">
      <div class="section-header">
        <div>
          <h2 id="category-heading">{{ t('home.categories.label') }}</h2>
          <p>{{ sectionCopy.subtitle }}</p>
        </div>
      </div>
      <div class="grid">
        <article v-for="cat in categoryCards" :key="`cat-${cat.label}`" class="col-6 md:col-4 xl:col-2">
          <div class="category-card surface-card border-round-2xl shadow-1">
            <div class="category-thumb" :style="{ backgroundImage: `url(${cat.image})` }"></div>
            <p class="category-label">{{ cat.label }}</p>
          </div>
        </article>
      </div>
    </section>

    <section class="content-section featured" aria-labelledby="featured-heading">
      <div class="section-header">
        <div>
          <h2 id="featured-heading">{{ sectionCopy.title }}</h2>
          <p>{{ sectionCopy.subtitle }}</p>
        </div>
        <Button
          :label="t('home.actions.exploreAll')"
          icon="pi pi-arrow-right"
          class="p-button-text"
          @click="$router.push({ name: 'catalogue' })"
        />
      </div>

      <div class="product-grid">
        <article v-for="product in featuredProducts" :key="product.title" class="product-card">
          <div class="product-thumb" :style="{ backgroundImage: `url(${product.image})` }" />
          <div class="product-body">
            <p class="product-name">{{ product.title }}</p>
            <p class="product-price">{{ pricePrefix }} $ {{ product.price }}</p>
          </div>
        </article>
      </div>
    </section>

    <div class="perks-grid">
      <div v-for="perk in perks" :key="perk.label" class="perk">
        <span :class="['pi', perk.icon]" />
        <p>{{ perk.label }}</p>
      </div>
    </div>

    <section class="content-section flash-deals" aria-labelledby="flash-deals-heading">
      <div class="flash-card border-round-3xl">
        <div class="flash-copy">
          <p class="flash-badge">{{ flashDeals[0].badge }}</p>
          <h2 id="flash-deals-heading">{{ flashDeals[0].title }}</h2>
          <p>{{ flashDeals[0].subtitle }}</p>
          <Button :label="flashDeals[0].cta" icon="pi pi-bolt" class="p-button-warning" @click="$router.push({ name: 'catalogue', query: { promotion: 'featured' } })" />
        </div>
        <div class="flash-visual" :style="{ backgroundImage: `url(${flashDeals[0].image})` }"></div>
      </div>
    </section>

    <section class="content-section collections" aria-labelledby="collections-heading">
      <div class="section-header">
        <div>
          <h2 id="collections-heading">Collections à explorer</h2>
          <p>Inspirez-vous et composez vos looks par univers.</p>
        </div>
      </div>
      <div class="collections-grid">
        <article v-for="(col, index) in collections" :key="`collection-${index}`" class="collection-card">
          <div class="collection-thumb" :style="{ backgroundImage: `url(${col.image})` }"></div>
          <div class="collection-body">
            <h3>{{ col.title }}</h3>
            <Button label="Découvrir" icon="pi pi-arrow-right" text @click="$router.push({ name: 'catalogue' })" />
          </div>
        </article>
      </div>
    </section>

    <section class="content-section" aria-labelledby="top-rated-heading">
      <div class="section-header">
        <div>
          <h2 id="top-rated-heading">{{ topRatedSection.title }}</h2>
          <p>{{ topRatedSection.subtitle }}</p>
        </div>
        <Button
          v-if="topRatedProducts.length"
          :label="topRatedCta"
          icon="pi pi-arrow-right"
          class="p-button-text"
          @click="$router.push({ name: 'catalogue', query: { sort: 'rating' } })"
        />
      </div>

      <div class="product-grid">
        <article v-for="(item, index) in topRatedProducts" :key="`top-rated-${item.id}-${index}`" class="product-card">
          <div class="product-thumb" :style="{ backgroundImage: `url(${item.image || getMockImage(index)})` }"></div>
          <div class="product-body">
            <div class="flex justify-content-between align-items-start gap-3">
              <p class="product-name">{{ item.name }}</p>
              <Tag value="★ 4.9" severity="warning" class="rating-tag" />
            </div>
            <p class="product-price" v-if="formattedPrice(item)">{{ formattedPrice(item) }}</p>
            <p class="product-description" v-else>{{ item.description }}</p>
            <Button
              :label="t('home.actions.viewProduct')"
              icon="pi pi-eye"
              text
              size="small"
              @click="$router.push(resolveProductLink(item))"
            />
          </div>
        </article>
      </div>
    </section>

    <section class="content-section promotions" aria-labelledby="top-promotions-heading">
      <div class="section-header">
        <div>
          <h2 id="top-promotions-heading">{{ topPromotionsSection.title }}</h2>
          <p>{{ topPromotionsSection.subtitle }}</p>
        </div>
        <Button
          v-if="topPromotions.length"
          :label="topPromotionsCta"
          icon="pi pi-percentage"
          class="p-button-outlined"
          @click="$router.push({ name: 'catalogue', query: { promotion: 'featured' } })"
        />
      </div>

      <div class="promotions-grid">
        <article
          v-for="(promotion, index) in topPromotions"
          :key="`promotion-${promotion.id}-${index}`"
          class="promotion-card surface-card border-round-2xl shadow-2"
        >
          <div
            class="promotion-media"
            :style="{ backgroundImage: `url(${promotion.image || getMockImage(index + 3)})` }"
          >
            <Tag v-if="promotion.badge" :value="promotion.badge" severity="danger" class="promotion-badge" />
          </div>
          <div class="promotion-body">
            <h3 class="promotion-title">{{ promotion.name }}</h3>
            <p class="promotion-description">{{ promotion.description }}</p>
            <div class="promotion-actions">
              <Button
                :label="t('home.actions.shopNow')"
                icon="pi pi-shopping-bag"
                class="p-button-sm"
                @click="$router.push(resolveProductLink(promotion))"
              />
              <span v-if="formattedPrice(promotion)" class="promotion-price">{{ formattedPrice(promotion) }}</span>
            </div>
          </div>
        </article>
      </div>
    </section>

    <section class="content-section" aria-labelledby="new-products-heading">
      <div class="section-header">
        <div>
          <h2 id="new-products-heading">{{ newProductsSection.title }}</h2>
          <p>{{ newProductsSection.subtitle }}</p>
        </div>
        <Button
          v-if="newProducts.length"
          :label="newProductsCta"
          icon="pi pi-plus-circle"
          class="p-button-link"
          @click="$router.push({ name: 'catalogue', query: { sort: 'latest' } })"
        />
      </div>

      <div class="new-grid">
        <article
          v-for="(item, index) in newProducts"
          :key="`new-product-${item.id}-${index}`"
          class="new-card surface-card border-round-2xl shadow-1"
        >
          <div class="new-card-media" :style="{ backgroundImage: `url(${item.image || getMockImage(index + 6)})` }"></div>
          <div class="new-card-body">
            <div class="flex justify-content-between align-items-start gap-3">
              <h3 class="new-card-title">{{ item.name }}</h3>
              <Tag value="New" severity="info" class="uppercase" />
            </div>
            <p class="new-card-description">{{ item.description }}</p>
            <div class="flex justify-content-between align-items-center">
              <span class="new-card-price" v-if="formattedPrice(item)">{{ formattedPrice(item) }}</span>
              <Button
                :label="t('home.actions.viewProduct')"
                icon="pi pi-eye"
                text
                size="small"
                @click="$router.push(resolveProductLink(item))"
              />
            </div>
          </div>
        </article>
      </div>
    </section>

<section class="content-section popular" aria-labelledby="popular-heading">
  <div class="section-header">
    <div>
      <h2 id="popular-heading">Produits populaires</h2>
      <p>Les essentiels préférés de la communauté.</p>
    </div>
    <Button :label="t('home.actions.exploreAll')" icon="pi pi-arrow-right" class="p-button-text" @click="$router.push({ name: 'catalogue' })" />
  </div>
  <div class="product-grid">
    <article v-for="(item, index) in popularProducts" :key="`popular-${index}`" class="product-card">
      <div class="product-thumb" :style="{ backgroundImage: `url(${item.image})` }"></div>
      <div class="product-body">
        <p class="product-name">{{ item.title }}</p>
        <p class="product-price">{{ item.price }}</p>
      </div>
    </article>
  </div>
</section>

<section class="content-section partners" aria-labelledby="partners-heading">
  <div class="section-header">
    <div>
      <h2 id="partners-heading">Nos partenaires</h2>
      <p>Marques sélectionnées pour la qualité et le style.</p>
    </div>
  </div>
  <div class="partners-grid">
    <div v-for="seller in partnerSellers" :key="seller.name" class="partner-card surface-card border-round-xl">
      <div class="partner-logo" :style="{ backgroundImage: `url(${seller.logo})` }"></div>
      <p class="partner-name">{{ seller.name }}</p>
    </div>
  </div>
</section>
</section>
</template>

<style scoped>
.home {
  display: flex;
  flex-direction: column;
  gap: 2.5rem;
}

.hero {
  min-height: 440px;
  border-radius: 32px;
  background: linear-gradient(120deg, #ffdd9a, #ffbb42);
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  overflow: hidden;
  position: relative;
  padding: clamp(1.5rem, 4vw, 3rem);
}

.hero::after {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at top, rgba(255, 255, 255, 0.35), transparent);
  pointer-events: none;
}

.hero-content {
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  color: #201600;
}

.hero-tag {
  width: max-content;
  background: rgba(255, 255, 255, 0.85);
  color: #a66300;
  border-radius: 999px;
  font-weight: 600;
}

.hero-subtitle {
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.3em;
  font-size: 0.9rem;
}

.hero-title {
  margin: 0;
  font-size: clamp(2.5rem, 5vw, 3.8rem);
  font-weight: 800;
  text-transform: uppercase;
  line-height: 1.1;
}

.hero-cta {
  width: max-content;
  border-radius: 999px;
  padding: 1rem 3rem;
  background: #111 !important;
  border: none !important;
  color: #fff !important;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  box-shadow: 0 18px 35px rgba(0, 0, 0, 0.35);
}

.hero-cta :deep(.p-button-icon-right) {
  margin-left: 0.75rem;
}

.hero-cta:hover,
.hero-cta:focus-visible {
  background: #ffffff !important;
  color: #111 !important;
  border: none !important;
  outline: none;
}

.hero-cta:hover :deep(.p-button-icon-right),
.hero-cta:focus-visible :deep(.p-button-icon-right) {
  color: #111;
}

.hero-visual {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.hero-image {
  width: min(420px, 100%);
  aspect-ratio: 3 / 4;
  background-size: cover;
  background-position: center;
  border-radius: 40px;
  box-shadow: 0 30px 60px rgba(17, 17, 17, 0.35);
}

.hero-dots {
  position: absolute;
  bottom: 1.25rem;
  display: flex;
  gap: 0.5rem;
}

.dot {
  width: 12px;
  height: 12px;
  border-radius: 999px;
  border: none;
  background: rgba(255, 255, 255, 0.5);
  cursor: pointer;
  transition: width 0.2s ease, background 0.2s ease;
}

.dot.active {
  width: 36px;
  background: #111;
}

.categories-ribbon {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: center;
  background: #fff;
  border-radius: 999px;
  padding: 0.5rem 1rem;
  box-shadow: 0 10px 25px rgba(15, 15, 15, 0.08);
}

.ribbon-label {
  font-weight: 700;
  color: #ff9b05;
  letter-spacing: 0.2em;
  text-transform: uppercase;
}

.chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.chip {
  border: none;
  border-radius: 999px;
  padding: 0.55rem 1.25rem;
  background: #111;
  color: #fff;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s ease, background 0.2s ease;
}

.chip:hover {
  transform: translateY(-2px);
  background: #ff7a18;
}

.section-header {
  text-align: center;
}

.section-header h2 {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
}

.section-header p {
  margin: 0.25rem 0 0;
  color: #6b6b6b;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
}

.product-card {
  border-radius: 24px;
  background: #fff;
  box-shadow: 0 18px 40px rgba(26, 26, 26, 0.08);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.product-thumb {
  width: 100%;
  aspect-ratio: 4 / 3;
  background-size: cover;
  background-position: center;
}

.product-body {
  padding: 1rem 1.5rem 1.5rem;
}

.product-name {
  margin: 0;
  font-weight: 700;
  font-size: 1.15rem;
}

.product-price {
  margin: 0.35rem 0 0;
  color: #ff7a18;
  font-weight: 700;
}

.perks-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
}

.perk {
  background: #fff;
  border-radius: 20px;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.05);
  font-weight: 600;
}

.perk span {
  font-size: 1.4rem;
  color: #ff9b05;
}

.category-grid {
  background: linear-gradient(135deg, #fff7e0, #ffffff);
  padding: 2rem;
  border-radius: 32px;
  box-shadow: 0 18px 40px -24px rgba(0, 0, 0, 0.18);
  border: 1px solid var(--surface-border);
}

.category-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  transition: transform var(--transition-fast), box-shadow var(--transition-fast);
  border: 1px solid var(--surface-border);
}

.category-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 18px 30px -14px rgba(0, 0, 0, 0.18);
}

.category-thumb {
  width: 100%;
  aspect-ratio: 1;
  border-radius: 18px;
  background-size: cover;
  background-position: center;
}

.category-label {
  margin: 0;
  font-weight: 700;
  text-align: center;
}

.featured {
  background: #fffdf8;
  border-radius: 32px;
  padding: 2rem;
  box-shadow: 0 18px 40px -24px rgba(0, 0, 0, 0.16);
  border: 1px solid var(--surface-border);
}

.content-section {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.promotions {
  background: linear-gradient(135deg, rgba(255, 236, 179, 0.6), rgba(255, 212, 130, 0.3));
  padding: 2rem;
  border-radius: 32px;
}

.promotions .section-header h2,
.promotions .section-header p {
  color: #3a2609;
}

.product-description,
.new-card-description,
.promotion-description {
  color: #6b6b6b;
  margin: 0.75rem 0;
  line-height: 1.6;
}

.rating-tag {
  font-weight: 700;
  border-radius: 999px;
  background: rgba(255, 180, 0, 0.15) !important;
  color: #aa6b00 !important;
  border: none !important;
}

.promotions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 1.5rem;
}

.promotion-card {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: none;
}

.promotion-media {
  width: 100%;
  aspect-ratio: 4 / 3;
  background-size: cover;
  background-position: center;
  border-radius: 24px;
  position: relative;
}

.promotion-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  border-radius: 999px;
}

.promotion-body {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.promotion-title {
  margin: 0;
  font-size: 1.25rem;
}

.promotion-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.promotion-price {
  font-size: 1.1rem;
  font-weight: 700;
  color: #ff6b35;
}

.new-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.new-card {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.new-card-media {
  width: 100%;
  aspect-ratio: 4 / 3;
  background-size: cover;
  background-position: center;
  border-radius: 28px 28px 0 0;
}

.new-card-body {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.new-card-title {
  margin: 0;
  font-size: 1.2rem;
}

.new-card-price {
  font-weight: 700;
  color: #ff6b35;
}

.flash-deals {
  padding: 0;
}

.flash-card {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  overflow: hidden;
  background: linear-gradient(120deg, #fff4d8, #ffe6b3);
  box-shadow: 0 18px 40px -24px rgba(0, 0, 0, 0.22);
  border: 1px solid rgba(0, 0, 0, 0.04);
}

.flash-copy {
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.flash-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.85rem;
  border-radius: 999px;
  background: #111;
  color: #fff;
  font-weight: 700;
  width: max-content;
}

.flash-visual {
  min-height: 220px;
  background-size: cover;
  background-position: center;
}

.collections-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.25rem;
}

.collection-card {
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 18px 36px -22px rgba(0, 0, 0, 0.25);
  background: #fff;
  display: flex;
  flex-direction: column;
}

.collection-thumb {
  width: 100%;
  aspect-ratio: 4 / 3;
  background-size: cover;
  background-position: center;
}

.collection-body {
  padding: 1rem 1.25rem 1.25rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.popular .product-card {
  box-shadow: 0 14px 28px -20px rgba(0, 0, 0, 0.3);
  border: 1px solid var(--surface-border);
}

.partners-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 1rem;
}

.partner-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1.25rem;
  gap: 0.75rem;
  border: 1px solid var(--surface-border);
  background: #fff;
  box-shadow: 0 12px 24px -18px rgba(0, 0, 0, 0.18);
  transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.partner-logo {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background-size: cover;
  background-position: center;
  box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.05);
}

.partner-name {
  margin: 0;
  font-weight: 700;
}

.partner-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 30px -18px rgba(0, 0, 0, 0.24);
}

.promotions .p-button-sm {
  background: #111 !important;
  border: none !important;
}

.promotions .p-button-sm:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 24px rgba(17, 17, 17, 0.2);
}

.content-section .section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.content-section .section-header h2 {
  margin: 0;
}

.content-section .section-header p {
  margin: 0.25rem 0 0;
  color: #6b6b6b;
}

.content-section .section-header > div {
  max-width: 480px;
}

@media (max-width: 768px) {
  .hero {
    border-radius: 24px;
  }

  .categories-ribbon {
    border-radius: 24px;
  }

  .flash-card {
    grid-template-columns: 1fr;
  }

  .promotions {
    padding: 1.5rem;
  }

  .content-section .section-header {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
