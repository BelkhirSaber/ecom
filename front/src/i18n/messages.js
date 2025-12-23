const messages = {
  fr: {
    brand: { name: 'Eflyer' },
    nav: {
      utilityLinks: ['Meilleures ventes', 'Idées cadeaux', 'Nouveautés', 'Bonnes affaires', 'Service client'],
      actions: {
        wishlist: 'Favoris',
        cart: 'Panier',
        signIn: 'Connexion',
        backToShop: 'Retour boutique',
        account: 'Mon compte',
        logout: 'Déconnexion'
      },
      searchPlaceholder: 'Rechercher sur la boutique',
      categoryPlaceholder: 'Toutes catégories',
      languageLabel: 'Langue',
      categoryOptions: ['Toutes catégories', 'Mode', 'Électronique', 'Beauté', 'Maison']
    },
    footer: {
      text: '© {year} Eflyer — Créé avec Vue 3 & PrimeVue.',
      columns: {
        shop: {
          title: 'Boutique',
          links: {
            newIn: 'Nouveautés',
            bestSellers: 'Meilleures ventes',
            women: 'Collection Femme',
            men: 'Collection Homme'
          }
        },
        help: {
          title: 'Support',
          links: {
            faq: 'FAQ & aide',
            shipping: 'Livraison & retours',
            contact: 'Contact',
            stores: 'Nos boutiques'
          }
        },
        legal: {
          title: 'Légal',
          links: {
            privacy: 'Politique de confidentialité',
            terms: 'Conditions générales',
            cookies: 'Préférences cookies'
          }
        }
      },
      contact: {
        title: 'Besoin d’aide ?',
        email: 'hello@eflyer.shop',
        phone: '+33 1 86 95 42 00',
        schedule: 'Du lundi au vendredi, 9h à 18h'
      },
      socials: {
        title: 'Suivez-nous',
        instagram: 'Instagram',
        facebook: 'Facebook',
        tiktok: 'TikTok'
      }
    },
    marketing: {
      consent: {
        title: 'Optimisez votre expérience',
        description: 'Nous utilisons des pixels marketing (GA4, Meta, TikTok) pour analyser les performances et personnaliser les campagnes. Vous pouvez modifier votre choix à tout moment depuis votre compte.',
        accept: 'Accepter',
        decline: 'Refuser'
      }
    },
    home: {
      hero: {
        tag: 'EF 2025',
        slides: [
          {
            title: 'LANCEZ VOTRE SHOPPING FAVORI',
            subtitle: 'Nouvelles collections homme & femme 2025',
            cta: 'Acheter'
          },
          {
            title: 'LE MEILLEUR DES LOOKS PREMIUM',
            subtitle: 'Essentiels quotidiens revisités',
            cta: 'Découvrir'
          },
          {
            title: 'OFFRES FLASH DE LA SEMAINE',
            subtitle: 'Jusqu’à -40% sur les vestes mi-saison',
            cta: 'Profiter'
          }
        ]
      },
      categories: {
        label: 'Toutes catégories',
        items: ['Hommes', 'Femmes', 'Enfants', 'Accessoires', 'Chaussures', 'Beauté']
      },
      section: {
        title: 'Mode Homme & Femme',
        subtitle: 'Looks pointus & basiques intemporels livrés en 48h.'
      },
      products: {
        pricePrefix: 'Prix',
        items: [
          { title: 'T-shirt homme', price: 30, image: 'https://images.pexels.com/photos/7679468/pexels-photo-7679468.jpeg' },
          { title: 'Chemise homme', price: 30, image: 'https://images.pexels.com/photos/631157/pexels-photo-631157.jpeg' },
          { title: 'Foulard femme', price: 30, image: 'https://images.pexels.com/photos/2933774/pexels-photo-2933774.jpeg' }
        ]
      },
      perks: [
        { icon: 'pi pi-shield', label: 'Paiement sécurisé' },
        { icon: 'pi pi-gift', label: 'Emballage cadeau' },
        { icon: 'pi pi-refresh', label: 'Retour 30 jours' }
      ],
      actions: {
        viewProduct: 'Voir le produit',
        shopNow: 'Acheter maintenant',
        exploreAll: 'Tout explorer'
      },
      topRated: {
        title: 'Produits les mieux notés',
        subtitle: 'Adorés par la communauté, prêts à rejoindre votre dressing.',
        cta: 'Tous les avis',
        items: [
          {
            id: 101,
            name: 'Sneakers Signature',
            price: 129,
            currency: 'EUR',
            description: 'Cuir italien et semelle amortissante pour la ville.',
            image: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=80'
          },
          {
            id: 102,
            name: 'Manteau laine premium',
            price: 189,
            currency: 'EUR',
            description: 'Coupe droite, laine recyclée et doublure satin.',
            image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=900&q=80'
          },
          {
            id: 103,
            name: 'Sac cabas Iconic',
            price: 149,
            currency: 'EUR',
            description: 'Cuir vegan, poches multiples et bandoulière amovible.',
            image: 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=900&q=80'
          }
        ]
      },
      topPromotions: {
        title: 'Top promotions de la semaine',
        subtitle: 'Des offres exclusives pour profiter des meilleures remises.',
        cta: 'Voir toutes les offres',
        items: [
          {
            id: 201,
            name: 'Pack bien-être',
            description: 'Huiles essentielles + diffuseur design pour une ambiance relaxante.',
            badge: '-30%',
            price: 59,
            currency: 'EUR',
            image: 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=900&q=80'
          },
          {
            id: 202,
            name: 'Chemise lin respirante',
            description: 'Texture légère, parfaite pour l’été, disponible en 4 coloris.',
            badge: '-25%',
            price: 45,
            currency: 'EUR',
            image: 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=900&q=80'
          },
          {
            id: 203,
            name: 'Montre minimaliste',
            description: 'Mécanisme japonais, bracelet cuir camel, garantie 2 ans.',
            badge: '-40%',
            price: 89,
            currency: 'EUR',
            image: 'https://images.unsplash.com/photo-1518544889280-0f5ee5722f4e?auto=format&fit=crop&w=900&q=80'
          }
        ]
      },
      newProducts: {
        title: 'Nouveautés fraîchement arrivées',
        subtitle: 'Une sélection pointue des dernières pièces à ne pas manquer.',
        cta: 'Explorer les nouveautés',
        items: [
          {
            id: 301,
            name: 'Parka technique oversize',
            price: 169,
            currency: 'EUR',
            description: 'Imperméable, capuche doublée polaire et cordons ajustables.',
            image: 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=900&q=80'
          },
          {
            id: 302,
            name: 'Boots en suede sable',
            price: 159,
            currency: 'EUR',
            description: 'Semelle crantée, traité hydrofuge et doublure confortable.',
            image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=900&q=80'
          },
          {
            id: 303,
            name: 'Pantalon cargo urbain',
            price: 89,
            currency: 'EUR',
            description: 'Coupe tapered, poches utilitaires et matière stretch.',
            image: 'https://images.unsplash.com/photo-1512436991641-6745cdb1723f?auto=format&fit=crop&w=900&q=80'
          }
        ]
      }
    },
    auth: {
      login: {
        title: 'Connexion client',
        subtitle: 'Accédez à votre espace pour suivre vos commandes.',
        email: 'Adresse e-mail',
        emailPlaceholder: 'vous@example.com',
        password: 'Mot de passe',
        passwordPlaceholder: '••••••••',
        remember: 'Se souvenir de moi',
        forgotPassword: 'Mot de passe oublié ?',
        submit: 'Se connecter',
        noAccount: 'Pas encore de compte ?',
        createAccount: 'Créer un compte',
        success: 'Connexion réussie',
        welcome: 'Bienvenue !',
        error: 'Erreur de connexion',
        invalidCredentials: 'Identifiants invalides. Veuillez réessayer.'
      },
      register: {
        title: 'Créer un compte',
        subtitle: 'Rejoignez-nous pour profiter de tous nos services.',
        name: 'Nom complet',
        namePlaceholder: 'Votre nom',
        email: 'Adresse e-mail',
        emailPlaceholder: 'vous@example.com',
        password: 'Mot de passe',
        passwordPlaceholder: '••••••••',
        confirmPassword: 'Confirmer le mot de passe',
        submit: 'Créer mon compte',
        hasAccount: 'Déjà un compte ?',
        loginLink: 'Se connecter',
        success: 'Inscription réussie',
        welcome: 'Bienvenue parmi nous !',
        supportTitle: 'Service client dédié',
        supportDescription: 'Une styliste à votre écoute par chat ou téléphone pour toute question.'
      },
      highlights: {
        title: 'Points forts',
        items: ['Matières premium sourcées durablement', 'Coupe étudiée pour un parfait tomber', 'Finitions main et détails signature']
      }
    },
    cart: {
      title: 'Mon panier',
      empty: 'Votre panier est vide',
      continueShopping: 'Continuer mes achats',
      item: 'Article',
      price: 'Prix',
      quantity: 'Quantité',
      total: 'Total',
      subtotal: 'Sous-total',
      checkout: 'Commander',
      remove: 'Retirer'
    },
    cms: {
      error: 'Erreur',
      pageNotFound: 'Page introuvable'
    },
    checkout: {
      title: 'Finaliser la commande',
      firstName: 'Prénom',
      lastName: 'Nom',
      email: 'Email',
      phone: 'Téléphone',
      address: 'Adresse',
      city: 'Ville',
      postalCode: 'Code postal',
      country: 'Pays',
      paymentMethod: 'Mode de paiement',
      cashOnDelivery: 'Paiement à la livraison',
      placeOrder: 'Passer la commande',
      orderSummary: 'Récapitulatif',
      success: 'Commande validée',
      orderPlaced: 'Votre commande a été enregistrée',
      error: 'Erreur',
      failed: 'Échec de la commande'
    },
    account: {
      title: 'Mon compte',
      welcome: 'Bienvenue',
      logout: 'Déconnexion',
      myOrders: 'Mes commandes',
      noOrders: 'Aucune commande',
      startShopping: 'Commencer mes achats',
      orderId: 'N° commande',
      date: 'Date',
      total: 'Total',
      status: 'Statut',
      viewDetails: 'Voir détails',
      status_pending: 'En attente',
      status_processing: 'En cours',
      status_shipped: 'Expédiée',
      status_delivered: 'Livrée',
      status_cancelled: 'Annulée'
    }
  },
  en: {
    brand: { name: 'Eflyer' },
    nav: {
      utilityLinks: ['Best Sellers', 'Gift Ideas', 'New Releases', 'Today’s Deals', 'Customer Service'],
      actions: {
        wishlist: 'Wishlist',
        cart: 'Cart',
        signIn: 'Sign in',
        backToShop: 'Back to shop',
        account: 'My account',
        logout: 'Logout'
      },
      searchPlaceholder: 'Search the store',
      categoryPlaceholder: 'All categories',
      languageLabel: 'Language',
      categoryOptions: ['All categories', 'Fashion', 'Electronics', 'Beauty', 'Home']
    },
    footer: {
      text: '© {year} Eflyer — Crafted with Vue 3 & PrimeVue.',
      columns: {
        shop: {
          title: 'Shop',
          links: {
            newIn: 'New arrivals',
            bestSellers: 'Best sellers',
            women: 'Women collection',
            men: 'Men collection'
          }
        },
        help: {
          title: 'Support',
          links: {
            faq: 'Help & FAQ',
            shipping: 'Shipping & returns',
            contact: 'Contact',
            stores: 'Stores'
          }
        },
        legal: {
          title: 'Legal',
          links: {
            privacy: 'Privacy policy',
            terms: 'Terms & conditions',
            cookies: 'Cookie settings'
          }
        }
      },
      contact: {
        title: 'Need help?',
        email: 'hello@eflyer.shop',
        phone: '+33 1 86 95 42 00',
        schedule: 'Monday to Friday, 9am to 6pm'
      },
      socials: {
        title: 'Follow us',
        instagram: 'Instagram',
        facebook: 'Facebook',
        tiktok: 'TikTok'
      }
    },
    marketing: {
      consent: {
        title: 'Enhance your experience',
        description: 'We use marketing pixels (GA4, Meta, TikTok) to analyse performance and tailor campaigns. You can change your choice at any time from your account settings.',
        accept: 'Accept',
        decline: 'Decline'
      }
    },
    home: {
      hero: {
        tag: 'EF 2025',
        slides: [
          {
            title: 'GET YOUR FAVORITE SHOPPING STARTED',
            subtitle: 'New 2025 drops for women & men',
            cta: 'Buy now'
          },
          {
            title: 'THE ULTIMATE PREMIUM EDIT',
            subtitle: 'Daily essentials with style',
            cta: 'Discover'
          },
          {
            title: 'FLASH DEALS ALL WEEK',
            subtitle: 'Up to 40% off mid-season jackets',
            cta: 'Shop deals'
          }
        ]
      },
      categories: {
        label: 'All categories',
        items: ['Men', 'Women', 'Kids', 'Accessories', 'Shoes', 'Beauty']
      },
      section: {
        title: 'Men & Women Fashion',
        subtitle: 'Sharp looks & timeless basics delivered in 48h.'
      },
      products: {
        pricePrefix: 'Price',
        items: [
          { title: 'Men sweatshirt', price: 30, image: 'https://images.pexels.com/photos/7679468/pexels-photo-7679468.jpeg' },
          { title: 'Men shirt', price: 30, image: 'https://images.pexels.com/photos/631157/pexels-photo-631157.jpeg' },
          { title: 'Women scarf', price: 30, image: 'https://images.pexels.com/photos/2933774/pexels-photo-2933774.jpeg' }
        ]
      },
      perks: [
        { icon: 'pi pi-shield', label: 'Secure payment' },
        { icon: 'pi pi-gift', label: 'Gift wrapping' },
        { icon: 'pi pi-refresh', label: '30-day return' }
      ],
      actions: {
        viewProduct: 'View product',
        shopNow: 'Shop now',
        exploreAll: 'Explore all'
      },
      topRated: {
        title: 'Top rated products',
        subtitle: 'Loved by the community, ready to join your wardrobe.',
        cta: 'All reviews',
        items: [
          {
            id: 101,
            name: 'Signature sneakers',
            price: 129,
            currency: 'EUR',
            description: 'Italian leather and cushioned sole for city comfort.',
            image: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=80'
          },
          {
            id: 102,
            name: 'Premium wool coat',
            price: 189,
            currency: 'EUR',
            description: 'Straight cut, recycled wool and satin lining.',
            image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=900&q=80'
          },
          {
            id: 103,
            name: 'Iconic tote bag',
            price: 149,
            currency: 'EUR',
            description: 'Vegan leather, multiple pockets and removable strap.',
            image: 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=900&q=80'
          }
        ]
      },
      topPromotions: {
        title: 'Top promotions this week',
        subtitle: 'Exclusive offers to grab the best deals.',
        cta: 'See all offers',
        items: [
          {
            id: 201,
            name: 'Wellness set',
            description: 'Essential oils + design diffuser for a relaxing atmosphere.',
            badge: '-30%',
            price: 59,
            currency: 'EUR',
            image: 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=900&q=80'
          },
          {
            id: 202,
            name: 'Breathable linen shirt',
            description: 'Lightweight texture, perfect for summer, available in 4 colours.',
            badge: '-25%',
            price: 45,
            currency: 'EUR',
            image: 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=900&q=80'
          },
          {
            id: 203,
            name: 'Minimalist watch',
            description: 'Japanese movement, camel leather strap, 2-year warranty.',
            badge: '-40%',
            price: 89,
            currency: 'EUR',
            image: 'https://images.unsplash.com/photo-1518544889280-0f5ee5722f4e?auto=format&fit=crop&w=900&q=80'
          }
        ]
      },
      newProducts: {
        title: 'Freshly landed new items',
        subtitle: 'A curated selection of the latest must-have pieces.',
        cta: 'Browse new arrivals',
        items: [
          {
            id: 301,
            name: 'Oversized technical parka',
            price: 169,
            currency: 'EUR',
            description: 'Waterproof, fleece-lined hood and adjustable drawcords.',
            image: 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=900&q=80'
          },
          {
            id: 302,
            name: 'Sand suede boots',
            price: 159,
            currency: 'EUR',
            description: 'Lug sole, water-repellent treatment and cosy lining.',
            image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=900&q=80'
          },
          {
            id: 303,
            name: 'Urban cargo trousers',
            price: 89,
            currency: 'EUR',
            description: 'Tapered fit, utility pockets and stretch fabric.',
            image: 'https://images.unsplash.com/photo-1512436991641-6745cdb1723f?auto=format&fit=crop&w=900&q=80'
          }
        ]
      }
    },
    cms: {
      error: 'Error',
      pageNotFound: 'Page not found'
    },
    checkout: {
      title: 'Checkout',
      firstName: 'First name',
      lastName: 'Last name',
      email: 'Email',
      phone: 'Phone',
      address: 'Address',
      city: 'City',
      postalCode: 'Postal code',
      country: 'Country',
      paymentMethod: 'Payment method',
      cashOnDelivery: 'Cash on delivery',
      placeOrder: 'Place order',
      orderSummary: 'Order summary',
      success: 'Order confirmed',
      orderPlaced: 'Your order has been placed',
      error: 'Error',
      failed: 'Order failed'
    },
    account: {
      title: 'My Account',
      welcome: 'Welcome',
      logout: 'Logout',
      myOrders: 'My Orders',
      noOrders: 'No orders yet',
      startShopping: 'Start shopping',
      orderId: 'Order ID',
      date: 'Date',
      total: 'Total',
      status: 'Status',
      viewDetails: 'View details',
      status_pending: 'Pending',
      status_processing: 'Processing',
      status_shipped: 'Shipped',
      status_delivered: 'Delivered',
      status_cancelled: 'Cancelled'
    }
  },
  ar: {
    brand: { name: 'إفلاير' },
    nav: {
      utilityLinks: ['الأكثر مبيعًا', 'أفكار هدايا', 'إصدارات جديدة', 'عروض اليوم', 'خدمة العملاء'],
      actions: {
        wishlist: 'المفضلة',
        cart: 'السلة',
        signIn: 'تسجيل الدخول',
        backToShop: 'العودة للتسوق',
        account: 'حسابي',
        logout: 'تسجيل الخروج'
      },
      searchPlaceholder: 'ابحث في المتجر',
      categoryPlaceholder: 'كل الفئات',
      languageLabel: 'اللغة',
      categoryOptions: ['كل الفئات', 'أزياء', 'إلكترونيات', 'جمال', 'منزل']
    },
    footer: {
      text: '© {year} إفلاير — مبني بـ Vue 3 و PrimeVue.',
      columns: {
        shop: {
          title: 'المتجر',
          links: {
            newIn: 'إصدارات جديدة',
            bestSellers: 'الأكثر مبيعًا',
            women: 'مجموعة النساء',
            men: 'مجموعة الرجال'
          }
        },
        help: {
          title: 'الدعم',
          links: {
            faq: 'الأسئلة الشائعة',
            shipping: 'الشحن والإرجاع',
            contact: 'اتصل بنا',
            stores: 'معارضنا'
          }
        },
        legal: {
          title: 'قانوني',
          links: {
            privacy: 'سياسة الخصوصية',
            terms: 'الشروط والأحكام',
            cookies: 'إعدادات ملفات تعريف الارتباط'
          }
        }
      },
      contact: {
        title: 'بحاجة إلى مساعدة؟',
        email: 'hello@eflyer.shop',
        phone: '+33 1 86 95 42 00',
        schedule: 'من الاثنين إلى الجمعة، 9 صباحًا إلى 6 مساءً'
      },
      socials: {
        title: 'تابعنا',
        instagram: 'إنستغرام',
        facebook: 'فيسبوك',
        tiktok: 'تيك توك'
      }
    },
    marketing: {
      consent: {
        title: 'حسّن تجربتك',
        description: 'نستخدم بكسلات تسويقية (GA4، Meta، TikTok) لتحليل الأداء وتخصيص الحملات. يمكنك تغيير اختيارك في أي وقت من خلال حسابك.',
        accept: 'أوافق',
        decline: 'أرفض'
      }
    },
    home: {
      hero: {
        tag: 'EF 2025',
        slides: [
          {
            title: 'ابدأ تسوقك المفضل الآن',
            subtitle: 'مجموعات 2025 للنساء والرجال',
            cta: 'اشترِ الآن'
          },
          {
            title: 'أفضل الإطلالات الفاخرة',
            subtitle: 'أساسيات يومية بلمسة مميزة',
            cta: 'اكتشف'
          },
          {
            title: 'عروض فلاش هذا الأسبوع',
            subtitle: 'تخفيضات حتى 40٪ على معاطف الموسم',
            cta: 'تسوق العروض'
          }
        ]
      },
      categories: {
        label: 'كل الفئات',
        items: ['رجال', 'نساء', 'أطفال', 'إكسسوارات', 'أحذية', 'جمال']
      },
      section: {
        title: 'أزياء رجالية ونسائية',
        subtitle: 'إطلالات مختارة بعناية مع توصيل خلال 48 ساعة.'
      },
      products: {
        pricePrefix: 'السعر',
        items: [
          { title: 'قميص تي شيرت رجالي', price: 30, image: 'https://images.pexels.com/photos/7679468/pexels-photo-7679468.jpeg' },
          { title: 'قميص بأزرار', price: 30, image: 'https://images.pexels.com/photos/631157/pexels-photo-631157.jpeg' },
          { title: 'وشاح نسائي', price: 30, image: 'https://images.pexels.com/photos/2933774/pexels-photo-2933774.jpeg' }
        ]
      },
      perks: [
        { icon: 'pi pi-shield', label: 'دفع آمن' },
        { icon: 'pi pi-gift', label: 'تغليف هدايا' },
        { icon: 'pi pi-refresh', label: 'إرجاع خلال 30 يومًا' }
      ],
      actions: {
        viewProduct: 'عرض المنتج',
        shopNow: 'تسوق الآن',
        exploreAll: 'استكشاف الكل'
      },
      topRated: {
        title: 'أعلى المنتجات تقييمًا',
        subtitle: 'مفضلة مجتمعنا وجاهزة لتكمل إطلالتك.',
        cta: 'عرض كل المراجعات',
        items: [
          {
            id: 101,
            name: 'حذاء سنكيرز بتوقيع خاص',
            price: 129,
            currency: 'EUR',
            description: 'جلد إيطالي ونعل مريح مثالي للمدينة.',
            image: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=80'
          },
          {
            id: 102,
            name: 'معطف صوف فاخر',
            price: 189,
            currency: 'EUR',
            description: 'قصة مستقيمة من صوف معاد تدويره وبطانة ساتان.',
            image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=900&q=80'
          },
          {
            id: 103,
            name: 'حقيبة يد أيقونية',
            price: 149,
            currency: 'EUR',
            description: 'جلد نباتي بجيوب متعددة وحزام كتف قابل للإزالة.',
            image: 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=900&q=80'
          }
        ]
      },
      topPromotions: {
        title: 'أفضل العروض لهذا الأسبوع',
        subtitle: 'عروض حصرية للحصول على أفضل التخفيضات.',
        cta: 'استعراض كل العروض',
        items: [
          {
            id: 201,
            name: 'طقم استرخاء منزلي',
            description: 'زيوت عطرية + موزع أنيق لأجواء مريحة.',
            badge: '-30%',
            price: 59,
            currency: 'EUR',
            image: 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=900&q=80'
          },
          {
            id: 202,
            name: 'قميص كتان خفيف',
            description: 'خامة خفيفة مثالية للصيف ومتوفرة بأربعة ألوان.',
            badge: '-25%',
            price: 45,
            currency: 'EUR',
            image: 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=900&q=80'
          },
          {
            id: 203,
            name: 'ساعة Minimalist',
            description: 'مكينة يابانية وسوار جلد بلون الكاميل مع ضمان سنتين.',
            badge: '-40%',
            price: 89,
            currency: 'EUR',
            image: 'https://images.unsplash.com/photo-1518544889280-0f5ee5722f4e?auto=format&fit=crop&w=900&q=80'
          }
        ]
      },
      newProducts: {
        title: 'وصلت حديثًا',
        subtitle: 'اختيار مميز من أحدث القطع التي لا تفوت.',
        cta: 'اكتشف الجديد',
        items: [
          {
            id: 301,
            name: 'معطف باركا تقني واسع',
            price: 169,
            currency: 'EUR',
            description: 'مقاوم للماء مع بطانة دافئة وحبال قابلة للتعديل.',
            image: 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=900&q=80'
          },
          {
            id: 302,
            name: 'حذاء بوت من الجلد السويدي',
            price: 159,
            currency: 'EUR',
            description: 'نعل متين مع معالجة عازلة للماء وبطانة مريحة.',
            image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=900&q=80'
          },
          {
            id: 303,
            name: 'بنطال كارغو عصري',
            price: 89,
            currency: 'EUR',
            description: 'قصة ضيقة مع جيوب عملية وقماش مطاطي.',
            image: 'https://images.unsplash.com/photo-1512436991641-6745cdb1723f?auto=format&fit=crop&w=900&q=80'
          }
        ]
      }
    },
    auth: {
      login: {
        title: 'تسجيل دخول العميل',
        subtitle: 'الوصول إلى حسابك لتتبع طلباتك.',
        email: 'عنوان البريد الإلكتروني',
        emailPlaceholder: 'you@example.com',
        password: 'كلمة المرور',
        passwordPlaceholder: '••••••••',
        remember: 'تذكرني',
        forgotPassword: 'نسيت كلمة المرور؟',
        submit: 'تسجيل الدخول',
        noAccount: 'ليس لديك حساب؟',
        createAccount: 'إنشاء حساب',
        success: 'تم تسجيل الدخول بنجاح',
        welcome: 'مرحبًا!',
        error: 'خطأ في تسجيل الدخول',
        invalidCredentials: 'بيانات اعتماد غير صالحة. يرجى المحاولة مرة أخرى.'
      },
      register: {
        title: 'إنشاء حساب',
        subtitle: 'انضم إلينا للاستفادة من جميع خدماتنا.',
        name: 'الاسم الكامل',
        namePlaceholder: 'اسمك',
        email: 'عنوان البريد الإلكتروني',
        emailPlaceholder: 'you@example.com',
        password: 'كلمة المرور',
        passwordPlaceholder: '••••••••',
        confirmPassword: 'تأكيد كلمة المرور',
        submit: 'إنشاء حسابي',
        hasAccount: 'هل لديك حساب بالفعل؟',
        loginLink: 'تسجيل الدخول',
        success: 'تم التسجيل بنجاح',
        welcome: 'مرحبًا بك معنا!',
        error: 'خطأ في التسجيل',
        failed: 'فشل التسجيل. يرجى المحاولة مرة أخرى.'
      }
    },
    catalogue: {
      title: 'الكتالوج',
      description: 'اكتشف مجموعة منتجاتنا',
      search: 'بحث',
      category: 'الفئة',
      stock: 'المخزون',
      allCategories: 'الكل',
      allStock: 'الكل',
      inStock: 'متوفر',
      outOfStock: 'نفذ',
      loading: 'جارٍ التحميل...',
      noProducts: 'لم يتم العثور على منتجات',
      price: 'السعر',
      digital: 'رقمي',
      physical: 'مادي',
      noDescription: 'لا يوجد وصف متاح',
      viewDetails: 'عرض تفاصيل المنتج',
      errorSummary: 'خطأ',
      errorDetail: 'تعذر تحميل المنتجات'
    },
    product: {
      error: 'خطأ',
      notFound: 'المنتج غير موجود',
      outOfStock: 'نفذ من المخزون',
      inStock: 'متوفر',
      preorder: 'طلب مسبق',
      cannotAdd: 'هذا المنتج غير متوفر',
      addedToCart: 'تمت الإضافة إلى السلة',
      selectVariant: 'اختر نوعًا',
      quantity: 'الكمية',
      addToCart: 'أضف إلى السلة',
      description: 'الوصف',
      relatedTitle: 'منتجات تكمل إطلالتك',
      info: {
        shippingTitle: 'الشحن والإرجاع',
        shippingDescription: 'توصيل سريع خلال 48 ساعة مجانًا للطلبات التي تتجاوز 150€. إرجاع مجاني خلال 30 يومًا.',
        warrantyTitle: 'الجودة والضمان',
        warrantyDescription: 'ورشاتنا المعتمدة تضمن منتجات متينة ومسؤولة.',
        supportTitle: 'دعم عملاء مخصص',
        supportDescription: 'فريق الأناقة متاح للإجابة عن استفساراتك عبر الدردشة أو الهاتف.'
      },
      highlights: {
        title: 'ميزات أساسية',
        items: ['خامات فاخرة مستدامة', 'قصة مصممة بإتقان', 'تشطيبات يدوية بتفاصيل مميزة']
      }
    },
    cart: {
      title: 'سلتي',
      empty: 'سلتك فارغة',
      continueShopping: 'متابعة التسوق',
      item: 'المنتج',
      price: 'السعر',
      quantity: 'الكمية',
      total: 'الإجمالي',
      subtotal: 'المجموع الفرعي',
      checkout: 'إتمام الطلب',
      remove: 'إزالة'
    },
    cms: {
      error: 'خطأ',
      pageNotFound: 'الصفحة غير موجودة'
    },
    checkout: {
      title: 'إتمام الطلب',
      firstName: 'الاسم الأول',
      lastName: 'اسم العائلة',
      email: 'البريد الإلكتروني',
      phone: 'الهاتف',
      address: 'العنوان',
      city: 'المدينة',
      postalCode: 'الرمز البريدي',
      country: 'البلد',
      paymentMethod: 'طريقة الدفع',
      cashOnDelivery: 'الدفع عند الاستلام',
      placeOrder: 'تأكيد الطلب',
      orderSummary: 'ملخص الطلب',
      success: 'تم تأكيد الطلب',
      orderPlaced: 'تم تسجيل طلبك',
      error: 'خطأ',
      failed: 'فشل الطلب'
    },
    account: {
      title: 'حسابي',
      welcome: 'مرحبًا',
      logout: 'تسجيل الخروج',
      myOrders: 'طلباتي',
      noOrders: 'لا توجد طلبات',
      startShopping: 'ابدأ التسوق',
      orderId: 'رقم الطلب',
      date: 'التاريخ',
      total: 'الإجمالي',
      status: 'الحالة',
      viewDetails: 'عرض التفاصيل',
      status_pending: 'قيد الانتظار',
      status_processing: 'قيد المعالجة',
      status_shipped: 'تم الشحن',
      status_delivered: 'تم التسليم',
      status_cancelled: 'ملغى'
    }
  }
};

export default messages;
