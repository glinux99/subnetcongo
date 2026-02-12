<template>
  <div class="min-h-screen bg-[#FDFFFF] text-slate-900 font-sans selection:bg-red-100 selection:text-red-600 overflow-x-hidden">

    <!-- --- [1] NAVIGATION & MOBILE DRAWER --- -->
    <nav :class="['fixed w-full z-[100] transition-all duration-700 px-6 py-3', scrolled ? 'bg-white/90 backdrop-blur-2xl shadow-2xl border-b border-slate-100 py-2' : 'bg-transparent']">
      <div class="max-w-[1400px] mx-auto flex justify-between items-center">
        <!-- Logo -->
        <div class="flex items-center gap-3 group cursor-pointer">
          <div class="relative overflow-hidden rounded-xl transition-transform duration-500 group-hover:scale-110">
            <img src="https://subnetcongo.net/assets/img/logo.png" alt="SubnetCongo" class="h-10 md:h-12 w-auto object-contain" />
          </div>
          <div class="hidden sm:block">
            <span class="text-xl font-black tracking-tighter text-[#003399]">SUBNET<span class="text-[#EE1C25]">CONGO</span></span>
            <p class="text-[8px] font-bold uppercase tracking-[0.3em] text-slate-500 -mt-1">Connecter & Éduquer</p>
          </div>
        </div>

        <!-- Menu Desktop (Hidden on Mobile) -->
        <div class="hidden lg:flex items-center gap-1 bg-slate-100/50 p-1.5 rounded-2xl border border-slate-200/50">
          <template v-for="item in navLinks" :key="item.name">
            <a :href="item.href" class="px-5 py-2 rounded-xl text-sm font-bold text-slate-600 hover:text-[#003399] hover:bg-white transition-all duration-300">
              {{ item.name }}
            </a>
          </template>
        </div>

        <!-- Desktop Actions & Burger Button -->
        <div class="flex items-center gap-3">
          <button class="hidden md:flex items-center gap-2 px-4 py-2 text-sm font-black text-[#003399] hover:bg-blue-50 rounded-xl transition-all">
            <i class="pi pi-user-plus"></i> Espace Client
          </button>

          <button class="hidden lg:block relative group overflow-hidden bg-[#EE1C25] text-white px-8 py-3 rounded-xl font-black shadow-lg shadow-red-200 transition-all hover:scale-105 active:scale-95">
            <span class="relative z-10">L'Académie</span>
            <div class="absolute inset-0 bg-[#003399] translate-y-full group-hover:translate-y-0 transition-transform duration-500"></div>
          </button>

          <!-- BURGER BUTTON MOBILE -->
          <button @click="isMenuOpen = true" class="lg:hidden flex items-center justify-center w-11 h-11 rounded-xl bg-[#003399] text-white shadow-lg active:scale-90 transition-all">
            <i class="pi pi-align-right text-lg"></i>
          </button>
        </div>
      </div>
    </nav>

    <!-- --- MOBILE DRAWER (SIDE MENU) --- -->
    <div class="fixed inset-0 z-[110] pointer-events-none">
        <!-- Backdrop (Flou derrière le menu) -->
        <transition name="fade">
            <div v-if="isMenuOpen" @click="isMenuOpen = false" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm pointer-events-auto"></div>
        </transition>

        <!-- Drawer Panel -->
        <transition name="drawer">
            <div v-if="isMenuOpen" class="absolute right-0 top-0 h-full w-[280px] sm:w-[350px] bg-white shadow-[-20px_0_60px_rgba(0,0,0,0.1)] pointer-events-auto flex flex-col">
                <!-- Header Drawer -->
                <div class="p-6 flex justify-between items-center border-b border-slate-50">
                    <img src="https://subnetcongo.net/assets/img/logo.png" class="h-8 w-auto" />
                    <button @click="isMenuOpen = false" class="w-10 h-10 flex items-center justify-center rounded-full bg-slate-50 text-slate-400 hover:text-red-500 transition-colors">
                        <i class="pi pi-times"></i>
                    </button>
                </div>

                <!-- Links -->
                <div class="flex-1 overflow-y-auto p-6 space-y-2">
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-6">Menu Principal</p>
                    <a v-for="item in navLinks" :key="item.name" :href="item.href" @click="isMenuOpen = false"
                       class="flex items-center justify-between p-4 rounded-2xl hover:bg-slate-50 group transition-all">
                        <span class="font-black text-slate-700 group-hover:text-[#003399]">{{ item.name }}</span>
                        <i class="pi pi-arrow-right text-[10px] text-slate-300 group-hover:text-[#EE1C25] group-hover:translate-x-1 transition-all"></i>
                    </a>

                    <div class="pt-8 space-y-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-4">Espaces dédiés</p>
                        <button class="w-full flex items-center gap-4 p-4 rounded-2xl bg-blue-50 text-[#003399] font-black">
                            <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center shadow-sm"><i class="pi pi-user"></i></div>
                            Espace Client
                        </button>
                        <button class="w-full flex items-center gap-4 p-4 rounded-2xl bg-red-50 text-[#EE1C25] font-black">
                            <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center shadow-sm"><i class="pi pi-star"></i></div>
                            Subnet Academy
                        </button>
                    </div>
                </div>

                <!-- Footer Drawer -->
                <div class="p-8 bg-slate-50 space-y-4">
                    <div class="flex justify-center gap-6">
                        <a href="#" class="text-slate-400 hover:text-[#003399]"><i class="pi pi-facebook"></i></a>
                        <a href="#" class="text-slate-400 hover:text-[#003399]"><i class="pi pi-linkedin"></i></a>
                        <a href="#" class="text-slate-400 hover:text-[#EE1C25]"><i class="pi pi-whatsapp"></i></a>
                    </div>
                    <p class="text-[10px] text-center font-bold text-slate-400">© 2024 SUBNETCONGO SARL</p>
                </div>
            </div>
        </transition>
    </div>

    <!-- --- [2] HERO SECTION AVEC CAROUSEL --- -->
    <header class="relative min-h-[110vh] flex items-center pt-20 overflow-hidden bg-[#001A4D]">
      <!-- Background Carousel -->
      <div class="absolute inset-0 z-0">
        <transition-group name="fade">
          <div v-for="(slide, index) in heroSlides" :key="slide.id" v-show="activeSlide === index" class="absolute inset-0">
            <img :src="slide.bgImage" class="w-full h-full object-cover opacity-30 mix-blend-overlay scale-110 animate-slow-zoom" />
          </div>
        </transition-group>
        <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-red-600/10 to-transparent"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-blue-600/20 rounded-full blur-[120px]"></div>
      </div>

      <div class="relative z-10 max-w-[1400px] mx-auto px-6 grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
        <!-- Texte Hero (Dynamique) -->
        <div class="lg:col-span-7 space-y-8">
          <transition name="slide-fade" mode="out-in">
            <div :key="activeSlide" class="space-y-8">
              <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-md border border-white/20 rounded-full text-white">
                <span class="flex h-2 w-2 rounded-full bg-red-500 animate-ping"></span>
                <span class="text-xs font-black uppercase tracking-widest">{{ heroSlides[activeSlide].badge }}</span>
              </div>

              <h1 class="text-5xl md:text-8xl font-black text-white leading-[1.05] tracking-tighter">
                {{ heroSlides[activeSlide].title }} <br/>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-orange-400 italic">
                  {{ heroSlides[activeSlide].highlight }}
                </span>
              </h1>

              <p class="text-xl text-blue-100/70 max-w-2xl font-medium leading-relaxed">
                {{ heroSlides[activeSlide].description }}
              </p>
            </div>
          </transition>

          <div class="flex flex-wrap gap-4 pt-4">
            <button class="bg-white text-blue-900 px-10 py-5 rounded-2xl font-black text-lg shadow-2xl hover:bg-red-500 hover:text-white transition-all transform hover:-translate-y-2 flex items-center gap-3 w-full sm:w-auto justify-center">
              {{ activeSlide === 0 ? 'Découvrir nos Solutions ISP' : 'Voir les Formations' }} <i class="pi pi-bolt"></i>
            </button>
            <div class="flex items-center gap-4 px-6 py-4 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10 w-full sm:w-auto justify-center">
              <div class="flex -space-x-4">
                <div v-for="i in 4" :key="i" class="w-10 h-10 rounded-full border-2 border-blue-900 bg-slate-800 flex items-center justify-center overflow-hidden shadow-lg">
                   <img :src="`https://i.pravatar.cc/100?u=${i+10}`" alt="user">
                </div>
              </div>
              <div>
                <p class="text-white font-bold text-sm">+15k Apprenants</p>
                <div class="flex text-amber-400 text-[10px]"><i class="pi pi-star-fill" v-for="i in 5" :key="i"></i></div>
              </div>
            </div>
          </div>

          <!-- Indicateurs de Carousel -->
          <div class="flex gap-3 pt-8">
            <button v-for="(slide, index) in heroSlides" :key="index" @click="activeSlide = index"
              :class="['h-1.5 transition-all duration-500 rounded-full', activeSlide === index ? 'w-12 bg-red-500' : 'w-4 bg-white/20 hover:bg-white/40']">
            </button>
          </div>
        </div>

        <!-- Visuel Montage Flottant (Hidden on Mobile for cleaner look) -->
        <div class="lg:col-span-5 relative hidden lg:block">
          <div class="relative z-20 rounded-[3.5rem] overflow-hidden border-[12px] border-white/5 shadow-2xl animate-float h-[600px]">
            <transition name="carousel-slide" mode="out-in">
                <img :key="activeSlide" :src="heroSlides[activeSlide].mainImage" class="w-full h-full object-cover" />
            </transition>
            <div class="absolute inset-0 bg-gradient-to-t from-blue-900/80 via-transparent to-transparent"></div>

            <transition name="slide-up" mode="out-in">
                <div :key="activeSlide" class="absolute bottom-10 left-6 right-6 bg-white/10 backdrop-blur-2xl p-6 rounded-3xl border border-white/20">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-[#EE1C25] rounded-2xl flex items-center justify-center text-white shadow-lg">
                            <i :class="['pi', heroSlides[activeSlide].icon, 'text-xl']"></i>
                        </div>
                        <div>
                            <p class="text-white font-black">{{ heroSlides[activeSlide].cardTitle }}</p>
                            <p class="text-blue-200 text-xs">{{ heroSlides[activeSlide].cardSub }}</p>
                        </div>
                    </div>
                </div>
            </transition>
          </div>
          <!-- Cercle Décoratif -->
          <div class="absolute -top-10 -right-10 w-40 h-40 bg-red-600 rounded-full mix-blend-overlay filter blur-3xl opacity-50 animate-pulse"></div>
        </div>
      </div>

      <!-- Curve Separator -->
      <div class="absolute bottom-0 left-0 w-full overflow-hidden leading-[0]">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none" class="relative block w-full h-[100px] fill-white">
            <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V120H0V95.8C58.05,110.19,132.31,109.58,192,108c66.21-1.74,135-15.44,129.39-51.56Z"></path>
        </svg>
      </div>
    </header>

    <!-- --- [3] SECTION ISP & INFRASTRUCTURE --- -->
    <section class="py-32 px-6 relative bg-white">
      <div class="max-w-[1400px] mx-auto">
        <div class="text-center max-w-3xl mx-auto mb-20 space-y-4">
            <h2 class="text-[#EE1C25] font-black uppercase tracking-[0.4em] text-sm italic">Le Réseau du Futur</h2>
            <h3 class="text-5xl md:text-6xl font-black text-slate-900 tracking-tight leading-tight">Nous connectons là où les autres <span class="text-[#003399]">s'arrêtent</span>.</h3>
            <p class="text-slate-500 font-medium text-lg">Expertise technique en installation d'antennes paraboliques, configuration Starlink, fibre optique et maintenance réseau.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div v-for="service in ispServices" :key="service.title"
                 class="group p-10 bg-slate-50 rounded-[3.5rem] border border-transparent hover:border-red-100 hover:bg-white hover:shadow-2xl transition-all duration-700">
                <div :class="`w-20 h-20 rounded-[2rem] flex items-center justify-center mb-8 bg-white shadow-xl group-hover:scale-110 transition-transform duration-500` ">
                    <i :class="`pi ${service.icon} text-3xl text-${service.color}-600` "></i>
                </div>
                <h4 class="text-2xl font-black text-slate-900 mb-4">{{ service.title }}</h4>
                <p class="text-slate-500 leading-relaxed mb-8 font-medium italic">{{ service.desc }}</p>
                <ul class="space-y-3 mb-10">
                    <li v-for="feat in service.features" :key="feat" class="flex items-center gap-3 text-sm font-bold text-slate-600">
                        <div class="w-5 h-5 rounded-full bg-red-50 flex items-center justify-center"><i class="pi pi-check text-[8px] text-[#EE1C25]"></i></div>
                        {{ feat }}
                    </li>
                </ul>
                <button class="w-full py-5 rounded-2xl bg-slate-900 text-white font-black group-hover:bg-[#003399] transition-all shadow-lg">
                    Demander un devis
                </button>
            </div>
        </div>
      </div>
    </section>

    <!-- --- [4] BENTO GRID ACADEMY --- -->
    <section class="py-24 px-6 bg-[#001A4D] overflow-hidden relative rounded-[4rem] mx-4">
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:40px_40px]"></div>
      </div>

      <div class="max-w-[1400px] mx-auto relative z-10">
        <div class="flex flex-col lg:flex-row justify-between items-end mb-20 gap-8">
            <div class="max-w-2xl">
                <h2 class="text-red-500 font-black uppercase tracking-[0.4em] text-sm mb-4 italic">Subnet Academy</h2>
                <h3 class="text-4xl md:text-7xl font-black text-white tracking-tighter leading-tight">Apprenez avec des <br/> <span class="italic text-blue-400 underline decoration-red-500 underline-offset-8">Leaders du Numérique</span>.</h3>
            </div>
            <button class="px-10 py-5 bg-white rounded-2xl font-black text-blue-900 hover:bg-red-500 hover:text-white transition-all shadow-xl">
                Tout le catalogue (450+)
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <!-- Featured Course -->
            <div class="md:col-span-8 bg-gradient-to-br from-blue-800 to-blue-950 rounded-[3rem] p-10 relative overflow-hidden group border border-white/10 shadow-2xl">
                <div class="relative z-10 flex flex-col h-full justify-between">
                    <span class="bg-red-600 text-white px-5 py-2 rounded-full text-[10px] font-black uppercase w-fit tracking-widest shadow-xl">Nouveau & Exclusif</span>
                    <div class="mt-20">
                        <h4 class="text-4xl md:text-5xl font-black text-white mb-6 max-w-xl">Expert en Ingénierie Réseaux & Installation VSAT</h4>
                        <p class="text-blue-200 text-lg mb-8 max-w-md leading-relaxed">Devenez un technicien certifié capable de déployer des infrastructures partout en Afrique.</p>
                        <div class="flex items-center gap-8">
                            <div class="flex items-center gap-3">
                                <i class="pi pi-clock text-red-500 text-xl"></i>
                                <span class="text-white font-black text-sm uppercase">120 Heures</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <i class="pi pi-briefcase text-red-500 text-xl"></i>
                                <span class="text-white font-black text-sm uppercase">Placement Garanti</span>
                            </div>
                        </div>
                    </div>
                </div>
                <img src="https://images.unsplash.com/photo-1558494949-ef010911182e?q=80&w=2070" class="absolute top-0 right-0 w-1/2 h-full object-cover opacity-20 group-hover:scale-110 transition-transform duration-1000" />
            </div>

            <!-- Side Courses -->
            <div class="md:col-span-4 space-y-6">
                <div v-for="c in courses" :key="c.id" class="bg-white/5 backdrop-blur-md rounded-[2.5rem] p-8 border border-white/10 hover:bg-white/10 transition-all cursor-pointer group shadow-lg">
                    <div class="flex gap-6 items-center">
                        <div class="w-16 h-16 rounded-2xl bg-blue-500/20 flex items-center justify-center text-blue-400 group-hover:bg-red-500 group-hover:text-white transition-all shadow-inner">
                            <i :class="`pi ${c.icon} text-2xl` "></i>
                        </div>
                        <div>
                            <h5 class="text-white font-black mb-1 text-lg line-clamp-1 italic">{{ c.title }}</h5>
                            <p class="text-blue-200/50 text-[10px] font-black uppercase tracking-widest">{{ c.category }}</p>
                        </div>
                    </div>
                </div>
                <!-- Support Card -->
                <div class="bg-[#EE1C25] rounded-[2.5rem] p-8 text-white flex items-center justify-between group cursor-pointer hover:scale-105 transition-all shadow-2xl shadow-red-500/20">
                    <div>
                        <span class="text-xl font-black block leading-none">Besoin d'aide ?</span>
                        <span class="text-xs font-bold text-red-100">Parler à un conseiller</span>
                    </div>
                    <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center text-red-600 shadow-xl group-hover:rotate-12 transition-all">
                        <i class="pi pi-whatsapp text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </section>

    <!-- --- [5] PHILOSOPHIE & ENGAGEMENT --- -->
    <section class="py-32 px-6">
        <div class="max-w-[1400px] mx-auto grid grid-cols-1 lg:grid-cols-2 gap-20 items-center">
            <div class="relative">
                <div class="absolute -top-10 -left-10 w-40 h-40 bg-red-100 rounded-full blur-3xl opacity-50"></div>
                <div class="grid grid-cols-2 gap-4">
                    <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=2070" class="rounded-[2.5rem] shadow-2xl h-80 w-full object-cover mt-12 transform hover:-rotate-2 transition-all duration-500" />
                    <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?q=80&w=2070" class="rounded-[2.5rem] shadow-2xl h-80 w-full object-cover transform hover:rotate-2 transition-all duration-500" />
                </div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-[#003399] p-10 rounded-full border-[12px] border-white text-white text-center shadow-2xl">
                    <p class="text-4xl font-black italic tracking-tighter">Depuis 10 ans</p>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80">Au service du Congo</p>
                </div>
            </div>

            <div class="space-y-10">
                <h2 class="text-[#EE1C25] font-black uppercase tracking-[0.4em] text-sm italic">Notre Engagement</h2>
                <h3 class="text-5xl md:text-6xl font-black text-slate-900 leading-tight tracking-tighter">Pas de digital sans infrastructure, pas d'infrastructure sans <span class="text-[#003399]">maîtrise</span>.</h3>
                <p class="text-xl text-slate-500 leading-relaxed font-medium">
                    SubnetCongo SARL est une entreprise panafricaine basée à Kinshasa qui combine les services de fournisseur d'accès internet (FAI) et un centre de formation technologique.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="point in ['Installation Antennes (VSAT/Starlink)', 'Déploiement Fibre Optique', 'E-Learning Certifiant', 'Support Technique 24/7']"
                         :key="point" class="flex items-center gap-5 bg-slate-50 p-6 rounded-2xl border border-slate-100 group hover:bg-white hover:border-red-200 transition-all shadow-sm">
                        <div class="w-12 h-12 bg-white rounded-xl shadow-md flex items-center justify-center text-[#EE1C25] group-hover:bg-[#EE1C25] group-hover:text-white transition-all">
                            <i class="pi pi-check text-xs font-bold"></i>
                        </div>
                        <span class="text-sm font-black text-slate-700 uppercase tracking-tight">{{ point }}</span>
                    </div>
                </div>

                <div class="pt-6">
                    <button class="bg-slate-900 text-white px-12 py-6 rounded-3xl font-black text-lg hover:bg-[#003399] transition-all flex items-center gap-5 group shadow-2xl">
                        Parler à un Expert <i class="pi pi-phone animate-bounce"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- --- [6] FOOTER TERMINAL --- -->
    <footer class="bg-slate-900 pt-32 pb-12 px-6 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-red-600/10 rounded-full blur-[100px]"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-blue-600/10 rounded-full blur-[100px]"></div>

        <div class="max-w-[1400px] mx-auto relative z-10 text-white">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-20 items-center pb-24 border-b border-white/5">
                <h4 class="text-4xl md:text-6xl font-black tracking-tighter italic">Restez connecté aux <br/> opportunités du numérique.</h4>
                <div class="flex flex-col sm:flex-row gap-4">
                    <input type="email" placeholder="votre@email.com" class="flex-1 bg-white/5 border border-white/10 rounded-2xl px-6 py-5 text-white focus:ring-2 focus:ring-red-500 outline-none transition-all font-bold placeholder:text-slate-600 shadow-inner" />
                    <button class="bg-[#EE1C25] text-white px-12 py-5 rounded-2xl font-black hover:bg-white hover:text-red-600 transition-all shadow-xl">S'abonner</button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-16 py-24">
                <div class="space-y-10">
                    <img src="https://subnetcongo.net/assets/img/logo.png" class="h-14 w-auto brightness-200" />
                    <p class="text-slate-400 font-bold leading-relaxed italic">
                        Siège Social : Avenue Colonel Mondjiba, Concession UtexAfrica, Kinshasa, RDC.
                    </p>
                    <div class="flex gap-4">
                        <a v-for="social in ['facebook', 'twitter', 'linkedin', 'instagram']" :key="social" href="#" class="w-12 h-12 bg-white/5 rounded-xl flex items-center justify-center text-white hover:bg-[#003399] transition-all border border-white/5">
                            <i :class="`pi pi-${social}` "></i>
                        </a>
                    </div>
                </div>

                <div v-for="section in footerLinks" :key="section.title">
                    <h5 class="text-white font-black uppercase text-xs tracking-[0.4em] mb-10 opacity-60">{{ section.title }}</h5>
                    <ul class="space-y-5">
                        <li v-for="link in section.links" :key="link">
                            <a href="#" class="text-slate-400 font-bold hover:text-red-500 transition-all flex items-center gap-2 group">
                                <span class="w-1 h-1 bg-slate-700 rounded-full group-hover:bg-red-500 transition-all"></span> {{ link }}
                            </a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h5 class="text-white font-black uppercase text-xs tracking-[0.4em] mb-10 opacity-60">Certification & Qualité</h5>
                    <div class="bg-white/5 p-8 rounded-[2.5rem] border border-white/10 space-y-6 shadow-inner">
                        <div class="flex items-center gap-5 group">
                            <div class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center group-hover:bg-red-500 transition-all"><i class="pi pi-verified text-xl text-red-500 group-hover:text-white"></i></div>
                            <span class="font-black text-sm uppercase">Partenaire Starlink</span>
                        </div>
                        <div class="flex items-center gap-5 group">
                            <div class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center group-hover:bg-blue-500 transition-all"><i class="pi pi-verified text-xl text-blue-500 group-hover:text-white"></i></div>
                            <span class="font-black text-sm uppercase">Licence ARPTC</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:row justify-between items-center gap-6 pt-12 border-t border-white/5 text-slate-500">
                <p class="font-black text-[10px] uppercase tracking-widest italic">© 2024 SUBNETCONGO SARL. TOUS DROITS RÉSERVÉS.</p>
                <div class="flex items-center gap-8 text-[10px] font-black uppercase tracking-widest">
                    <a href="#" class="hover:text-white">Confidentialité</a>
                    <a href="#" class="hover:text-white">Mentions Légales</a>
                    <a href="#" class="hover:text-white">CGV</a>
                </div>
            </div>
        </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

// --- ÉTATS ---
const scrolled = ref(false);
const isMenuOpen = ref(false); // État du menu latéral
const activeSlide = ref(0);

const handleScroll = () => {
    scrolled.value = window.scrollY > 50;
};

// --- DONNÉES ---
const navLinks = [
    { name: 'Solutions Internet', href: '#' },
    { name: 'Subnet Academy', href: '#' },
    { name: 'Génie Civil & Infra', href: '#' },
    { name: 'Partenariats', href: '#' },
    { name: 'Support', href: '#' }
];

const heroSlides = [
    {
        id: 1,
        badge: "Expertise Satellitaire",
        title: "Connectez-vous",
        highlight: "À l'Avenir du Congo.",
        description: "Installation d'antennes VSAT et solutions Starlink haut débit partout en RDC, même dans les zones les plus reculées.",
        bgImage: "https://images.unsplash.com/photo-1544652478-6653e09f18a2?q=80&w=2070",
        mainImage: "https://images.unsplash.com/photo-1573166364524-d9dbfd8bbf83?q=80&w=2069",
        icon: "pi-map-marker",
        cardTitle: "Installation VSAT & Fibre",
        cardSub: "Déploiement agile sur tout le territoire."
    },
    {
        id: 2,
        badge: "Subnet Academy",
        title: "Propulsez votre",
        highlight: "Carrière Technologique.",
        description: "Formations certifiantes en Réseaux Cisco, Cybersécurité et Cloud pour les futurs leaders du numérique en Afrique.",
        bgImage: "https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=2070",
        mainImage: "https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=2070",
        icon: "pi-book",
        cardTitle: "Certification CCNA",
        cardSub: "Apprentissage pratique avec labos réels."
    },
    {
        id: 3,
        badge: "Infrastructure & BTP",
        title: "Construisons les",
        highlight: "Ponts du Digital.",
        description: "Solutions complètes en génie civil, fibre optique et maintenance d'infrastructures pour les entreprises de standard international.",
        bgImage: "https://images.unsplash.com/photo-1558494949-ef010911182e?q=80&w=2070",
        mainImage: "https://images.unsplash.com/photo-1551703599-6b3e8379aa8c?q=80&w=2070",
        icon: "pi-cog",
        cardTitle: "Maintenance NOC 24/7",
        cardSub: "Support technique de proximité."
    }
];

const ispServices = [
    {
        title: "Antennes VSAT & Satellite",
        icon: "pi-globe",
        color: "blue",
        desc: "Connexion satellitaire haute performance pour les zones minières et forestières de la RDC.",
        features: ["Bande Ku & Ka", "Latence optimisée", "Audit site complet"]
    },
    {
        title: "Starlink Business Elite",
        icon: "pi-bolt",
        color: "red",
        desc: "Installateur agréé en RDC offrant une vitesse jusqu'à 300 Mbps et un support local dédié.",
        features: ["Kit Haute Performance", "IP Statique possible", "GTR 4h"]
    },
    {
        title: "Fibre Optique Dédiée",
        icon: "pi-sitemap",
        color: "blue",
        desc: "Liaisons louées et fibre optique symétrique pour les sièges sociaux et datacenters.",
        features: ["Uptime 99.99%", "Scalabilité immédiate", "Monitoring NOC"]
    }
];

const courses = [
    { id: 1, title: "Administration Réseaux Cisco CCNA", category: "Réseaux & Sécurité", icon: "pi-database" },
    { id: 2, title: "Audit & Cybersécurité des SI", category: "Sécurité", icon: "pi-lock" },
    { id: 3, title: "Cloud Computing & AWS Architect", category: "Cloud", icon: "pi-cloud" },
    { id: 4, title: "Installation VSat Niveau Expert", category: "Telecom", icon: "pi-wifi" }
];

const footerLinks = [
    {
        title: "Nos Solutions",
        links: ["VSAT Satellite", "Liaisons Fibre", "Starlink Business", "Audit Réseau", "Hosting Cloud"]
    },
    {
        title: "Éducation",
        links: ["Subnet Academy", "Certification Pro", "Cours Gratuits", "Bourses IT", "Espace Mentor"]
    }
];

let carouselTimer;
onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    carouselTimer = setInterval(() => {
        activeSlide.value = (activeSlide.value + 1) % heroSlides.length;
    }, 7000);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    clearInterval(carouselTimer);
});
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap');

body {
    font-family: 'Inter', sans-serif;
    background-color: #FDFFFF;
}

/* --- ANIMATIONS DRAWER (LATÉRAL) --- */
.drawer-enter-active, .drawer-leave-active {
  transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.drawer-enter-from, .drawer-leave-to {
  transform: translateX(100%);
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.4s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

/* --- AUTRES ANIMATIONS --- */
.slide-fade-enter-active, .slide-fade-leave-active {
  transition: all 0.6s ease-out;
}
.slide-fade-enter-from {
  opacity: 0;
  transform: translateY(20px);
}
.slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

.carousel-slide-enter-active, .carousel-slide-leave-active {
  transition: all 1s cubic-bezier(0.4, 0, 0.2, 1);
}
.carousel-slide-enter-from {
  transform: translateX(100%);
  opacity: 0;
}
.carousel-slide-leave-to {
  transform: translateX(-100%);
  opacity: 0;
}

.slide-up-enter-active, .slide-up-leave-active {
  transition: all 0.8s ease;
}
.slide-up-enter-from {
  opacity: 0;
  transform: translateY(40px);
}

@keyframes float {
    0% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(1deg); }
    100% { transform: translateY(0px) rotate(0deg); }
}

@keyframes slow-zoom {
    0% { transform: scale(1.1); }
    100% { transform: scale(1.2); }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.animate-slow-zoom {
    animation: slow-zoom 20s linear infinite alternate;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 8px;
}
::-webkit-scrollbar-track {
    background: #f1f1f1;
}
::-webkit-scrollbar-thumb {
    background: #003399;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #EE1C25;
}

.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
