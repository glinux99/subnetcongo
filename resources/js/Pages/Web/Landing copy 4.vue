<template>
  <div class="min-h-screen bg-[#FDFFFF] text-slate-900 font-sans selection:bg-red-100 selection:text-red-600 overflow-x-hidden">

    <!-- --- [SECTION 1] NAVIGATION COMPLEXE & MOBILE DRAWER --- -->
    <nav :class="['fixed w-full z-[100] transition-all duration-700 px-6 py-3', scrolled ? 'bg-white/95 backdrop-blur-3xl shadow-2xl border-b border-slate-100 py-2' : 'bg-transparent']">
      <div class="max-w-[1500px] mx-auto flex justify-between items-center">

        <!-- Logo & Burger Combo (Mobile focus) -->
        <div class="flex items-center gap-4">
          <!-- BURGER BUTTON MOBILE - DÉPLACÉ À GAUCHE -->
          <button @click="isMenuOpen = true" class="lg:hidden flex items-center justify-center w-12 h-12 rounded-lg bg-[#003399] text-white shadow-lg active:scale-90 transition-all">
            <i class="pi pi-bars text-xl"></i>
          </button>

          <div class="flex items-center gap-3 group cursor-pointer">
            <div class="relative overflow-hidden rounded-lg transition-transform duration-500 group-hover:rotate-6 group-hover:scale-110 shadow-lg">
              <img src="https://subnetcongo.net/assets/img/logo.png" alt="SubnetCongo" class="h-10 md:h-14 w-auto object-contain bg-white p-1" />
            </div>

          </div>
        </div>

        <!-- Menu Desktop Ultra-Large -->
        <div class="hidden lg:flex items-center gap-1 bg-slate-100 p-1.5 rounded-lg border border-white/50 shadow-inner">
          <template v-for="item in navLinks" :key="item.name">
            <div class="relative group">
              <a :href="item.href" class="px-6 py-2.5 rounded-[1.5rem] text-sm font-black text-slate-600 hover:text-[#003399] hover:bg-white transition-all duration-300 flex items-center gap-2">
                {{ item.name }}
                <i v-if="item.submenu" class="pi pi-chevron-down text-[10px] group-hover:rotate-180 transition-transform"></i>
              </a>
              <!-- Mega Menu Dropdown (Simulation) -->
              <div v-if="item.submenu" class="absolute top-full left-0 mt-4 w-[250px] bg-white rounded-3xl shadow-2xl border border-slate-100 p-4 opacity-0 translate-y-4 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-500">
                <div v-for="sub in item.submenu" :key="sub" class="p-3 hover:bg-slate-50 rounded-xl transition-colors cursor-pointer">
                  <p class="text-sm font-bold text-slate-800">{{ sub }}</p>
                </div>
              </div>
            </div>
          </template>
        </div>

        <!-- Actions de Navigation -->
        <div class="flex items-center gap-3">
          <div class="hidden xl:flex flex-col items-end mr-4">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Besoin d'aide ?</span>
            <span class="text-sm font-black text-[#003399] leading-none">+243 970 912 428</span>
          </div>

          <button class="hidden md:flex items-center gap-2 px-5 py-3 text-sm font-black text-[#003399] bg-blue-50/50 hover:bg-blue-100 rounded-lg transition-all">
            <i class="pi pi-user-circle"></i> Espace Client
          </button>

          <button @click="login" class="relative group overflow-hidden bg-[#EE1C25] text-white px-8 py-4 rounded-lg font-black shadow-xl shadow-red-200 transition-all hover:scale-105 active:scale-95">
            <span class="relative z-10 flex items-center gap-2">Rejoindre l'Académie <i class="pi pi-arrow-right"></i></span>
            <div class="absolute inset-0 bg-[#003399] translate-x-[-100%] group-hover:translate-x-0 transition-transform duration-500"></div>
          </button>
        </div>
      </div>
    </nav>

    <!-- --- MOBILE DRAWER (MENU À GAUCHE) --- -->
    <div class="fixed inset-0 z-[150] pointer-events-none">
        <!-- Backdrop -->
        <transition name="fade">
            <div v-if="isMenuOpen" @click="isMenuOpen = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-md pointer-events-auto"></div>
        </transition>

        <!-- Drawer Panel - PLACÉ À GAUCHE -->
        <transition name="drawer-left">
            <div v-if="isMenuOpen" class="absolute left-0 top-0 h-full w-[300px] sm:w-[400px] bg-white shadow-[20px_0_80px_rgba(0,0,0,0.2)] pointer-events-auto flex flex-col">
                <!-- Header Drawer -->
                <div class="p-8 flex justify-between items-center border-b border-slate-50 bg-slate-50/50">
                    <div class="flex flex-col">
                        <img src="https://subnetcongo.net/assets/img/logo.png" class="h-10 w-auto" />
                        <span class="text-[8px] font-black text-slate-400 uppercase mt-2 tracking-widest">Navigation Mobile</span>
                    </div>
                    <button @click="isMenuOpen = false" class="w-12 h-12 flex items-center justify-center rounded-lg bg-white text-slate-400 hover:text-red-500 shadow-sm transition-all border border-slate-100">
                        <i class="pi pi-times"></i>
                    </button>
                </div>

                <!-- Liens Drawer -->
                <div class="flex-1 overflow-y-auto p-8 space-y-4 custom-scrollbar">
                    <div v-for="(item, idx) in navLinks" :key="item.name"
                         class="transform transition-all duration-500"
                         :style="{ transitionDelay: `${idx * 100}ms` }">
                        <a :href="item.href" @click="isMenuOpen = false"
                           class="flex items-center justify-between p-5 rounded-[1.5rem] bg-slate-50 hover:bg-[#003399] group transition-all duration-300 shadow-sm">
                            <span class="font-black text-slate-700 group-hover:text-white flex items-center gap-3">
                                <i :class="['pi text-lg', item.icon || 'pi-circle-fill text-[8px]']"></i>
                                {{ item.name }}
                            </span>
                            <i class="pi pi-chevron-right text-[10px] text-slate-300 group-hover:text-white group-hover:translate-x-1 transition-all"></i>
                        </a>
                    </div>

                    <div class="pt-10 space-y-4 border-t border-slate-100">
                        <p class="text-[10px] font-black uppercase tracking-[0.4em] text-slate-400 mb-6">Services Prioritaires</p>
                        <button class="w-full flex items-center justify-between p-5 rounded-lg bg-blue-600 text-white font-black shadow-lg shadow-blue-200">
                            <div class="flex items-center gap-4">
                                <i class="pi pi-user text-xl"></i>
                                <span>Mon Compte FAI</span>
                            </div>
                            <i class="pi pi-external-link"></i>
                        </button>
                        <button class="w-full flex items-center gap-4 p-5 rounded-lg bg-red-600 text-white font-black shadow-lg shadow-red-200">
                            <i class="pi pi-graduation-cap text-xl"></i>
                            Portail Apprenant
                        </button>
                    </div>
                </div>

                <!-- Footer Drawer -->
                <div class="p-8 bg-slate-900 text-white">
                    <div class="flex justify-between items-center mb-6">
                        <a v-for="s in ['facebook', 'linkedin', 'twitter', 'whatsapp']" :key="s" href="#" class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center hover:bg-red-500 transition-all">
                            <i :class="`pi pi-${s}` "></i>
                        </a>
                    </div>
                    <p class="text-[10px] text-center font-bold text-slate-500 uppercase tracking-widest">Propulsé par SubnetCongo Tech</p>
                </div>
            </div>
        </transition>
    </div>

    <!-- --- [SECTION 2] HERO DYNAMIQUE MULTI-COUCHES --- -->
    <header class="relative min-h-[115vh] flex items-center pt-24 overflow-hidden bg-[#001A4D]">

      <!-- BACKGROUND ENGINE -->
      <div class="absolute inset-0 z-0">
        <transition-group name="fade-bg">
          <div v-for="(slide, index) in heroSlides" :key="slide.id" v-show="activeSlide === index" class="absolute inset-0">
            <div class="absolute inset-0 bg-black/40 z-10"></div>
            <img :src="slide.bgImage" class="w-full h-full object-cover scale-110 animate-slow-zoom" />
          </div>
        </transition-group>

        <!-- Overlays Décoratifs -->
        <div class="absolute inset-0 bg-gradient-to-b from-[#001A4D]/80 via-transparent to-[#001A4D]"></div>
        <div class="absolute top-0 right-0 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-20"></div>

        <!-- Blobs Lumineux -->
        <div class="absolute top-1/4 -left-20 w-[500px] h-[500px] bg-red-600/20 rounded-full blur-[150px] animate-pulse"></div>
        <div class="absolute bottom-0 -right-20 w-[600px] h-[600px] bg-blue-600/20 rounded-full blur-[180px]"></div>
      </div>

      <div class="relative z-20 max-w-[1500px] mx-auto px-6 grid grid-cols-1 lg:grid-cols-12 gap-20 items-center">

        <!-- TEXT CONTENT -->
        <div class="lg:col-span-7 space-y-10">
          <transition name="slide-fade-content" mode="out-in">
            <div :key="activeSlide" class="space-y-10">
              <div class="inline-flex items-center gap-3 px-6 py-3 bg-white/10 backdrop-blur-2xl border border-white/20 rounded-full text-white shadow-2xl">
                <span class="flex h-3 w-3 rounded-full bg-red-500 animate-ping"></span>
                <span class="text-xs font-black uppercase tracking-[0.3em]">{{ heroSlides[activeSlide].badge }}</span>
              </div>

              <h1 class="text-6xl md:text-[7.5rem] font-black text-white leading-[0.95] tracking-tighter">
                {{ heroSlides[activeSlide].title }} <br/>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 via-orange-400 to-yellow-300 italic">
                  {{ heroSlides[activeSlide].highlight }}
                </span>
              </h1>

              <p class="text-2xl text-blue-100/80 max-w-2xl font-medium leading-relaxed border-l-4 border-red-500 pl-8">
                {{ heroSlides[activeSlide].description }}
              </p>
            </div>
          </transition>

          <!-- CTAs -->
          <div class="flex flex-wrap gap-6 pt-6">
            <button class="group relative bg-white text-blue-900 px-12 py-6 rounded-lg font-black text-xl shadow-2xl hover:scale-105 transition-all duration-500 overflow-hidden">
              <span class="relative z-10 flex items-center gap-4">Commencer l'Aventure <i class="pi pi-bolt group-hover:rotate-12 transition-transform"></i></span>
              <div class="absolute inset-0 bg-red-500 translate-y-full group-hover:translate-y-0 transition-transform duration-500"></div>
            </button>

            <button class="flex items-center gap-4 px-10 py-6 bg-white/5 backdrop-blur-md rounded-lg border border-white/10 text-white font-black hover:bg-white/10 transition-all">
              <i class="pi pi-play-circle text-3xl text-red-500"></i>
              <span>Voir la vidéo</span>
            </button>
          </div>

          <!-- Social Proof -->
          <div class="flex items-center gap-8 pt-10 border-t border-white/10">
            <div class="flex -space-x-5">
              <img v-for="i in 5" :key="i" :src="`https://i.pravatar.cc/150?u=${i+50}`" class="w-14 h-14 rounded-full border-4 border-[#001A4D] shadow-xl hover:-translate-y-2 transition-transform" />
            </div>
            <div>
              <p class="text-white text-xl font-black">+25,000 Utilisateurs</p>
              <div class="flex items-center gap-2">
                <div class="flex text-amber-400 text-xs">
                    <i class="pi pi-star-fill" v-for="i in 5" :key="i"></i>
                </div>
                <span class="text-blue-200 text-xs font-bold font-mono tracking-widest">4.9/5 RATING</span>
              </div>
            </div>
          </div>
        </div>

        <!-- FLOATING VISUAL ENGINE -->
        <div class="lg:col-span-5 relative hidden lg:block">
          <div class="relative z-20 rounded-lg overflow-hidden border-[15px] border-white/5 shadow-[0_50px_100px_rgba(0,0,0,0.5)] animate-float h-[650px] group">
            <transition name="image-swap" mode="out-in">
                <img :key="activeSlide" :src="heroSlides[activeSlide].mainImage" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-[3s]" />
            </transition>

            <div class="absolute inset-0 bg-gradient-to-t from-blue-900/90 via-transparent to-transparent opacity-60"></div>

            <!-- Floating Data Card -->
            <transition name="slide-up-card" mode="out-in">
                <div :key="activeSlide" class="absolute bottom-12 left-8 right-8 bg-white/10 backdrop-blur-3xl p-8 rounded-lg border border-white/20 shadow-2xl">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-red-500 to-orange-600 rounded-lg flex items-center justify-center text-white shadow-xl rotate-3">
                            <i :class="['pi', heroSlides[activeSlide].icon, 'text-2xl']"></i>
                        </div>
                        <div>
                            <p class="text-white text-xl font-black mb-1">{{ heroSlides[activeSlide].cardTitle }}</p>
                            <p class="text-blue-200 text-sm font-medium opacity-80 uppercase tracking-widest">{{ heroSlides[activeSlide].cardSub }}</p>
                        </div>
                    </div>
                </div>
            </transition>
          </div>

          <!-- Abstract Circles -->
          <div class="absolute -top-10 -right-10 w-48 h-48 bg-[#EE1C25] rounded-full mix-blend-screen blur-3xl opacity-30 animate-pulse"></div>
          <div class="absolute -bottom-10 -left-10 w-64 h-64 bg-[#003399] rounded-full mix-blend-screen blur-[100px] opacity-40"></div>
        </div>
      </div>

      <!-- WAVE SEPARATOR -->
      <div class="absolute bottom-0 left-0 w-full overflow-hidden leading-[0] transform translate-y-[1px]">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none" class="relative block w-full h-[120px] fill-[#FDFFFF]">
            <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25"></path>
            <path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5V0Z" opacity=".5"></path>
            <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z"></path>
        </svg>
      </div>
    </header>

    <!-- --- [SECTION 3] STATISTIQUES --- -->
    <section class="py-20 relative bg-[#FDFFFF]">
        <div class="max-w-[1500px] mx-auto px-6">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                <div v-for="stat in statistics" :key="stat.label" class="p-10 rounded-lg bg-slate-50 border border-slate-100 hover:bg-white hover:shadow-2xl transition-all group">
                    <p class="text-5xl font-black text-[#003399] mb-2 group-hover:scale-110 transition-transform inline-block">{{ stat.value }}</p>
                    <p class="text-slate-500 font-black uppercase tracking-widest text-xs">{{ stat.label }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- --- [SECTION 4] ISP & INFRASTRUCTURE SERVICES --- -->
    <section class="py-32 px-6 relative bg-white overflow-hidden" id="solutions">
      <!-- Background Accents -->
      <div class="absolute top-0 right-0 w-1/3 h-full bg-slate-50 rounded-l-[10rem] -z-10 translate-x-1/2"></div>

      <div class="max-w-[1500px] mx-auto">
        <div class="flex flex-col lg:flex-row justify-between items-end mb-24 gap-10">
            <div class="max-w-3xl space-y-6">
                <div class="inline-flex items-center gap-2 text-[#EE1C25]">
                    <span class="w-12 h-[2px] bg-[#EE1C25]"></span>
                    <span class="font-black uppercase tracking-[0.5em] text-sm italic">Le Réseau du Futur</span>
                </div>
                <h3 class="text-6xl md:text-7xl font-black text-slate-900 tracking-tighter leading-[0.9]">Nous connectons là où les autres <span class="text-[#003399] underline decoration-red-500 decoration-8 underline-offset-[10px]">s'arrêtent</span>.</h3>
            </div>
            <p class="text-xl text-slate-500 font-medium max-w-sm border-l-2 border-slate-200 pl-6 italic">Expertise technique en installation d'antennes paraboliques, configuration Starlink, fibre optique et maintenance réseau.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
            <div v-for="(service, idx) in ispServices" :key="service.title"
                 class="group relative p-12 bg-slate-50 rounded-lg border border-transparent hover:border-red-100 hover:bg-white hover:shadow-[0_40px_80px_rgba(0,0,0,0.08)] transition-all duration-700 overflow-hidden">

                <div class="absolute -top-10 -right-10 text-9xl font-black text-slate-100/50 italic group-hover:text-red-500/5 transition-colors">{{ idx + 1 }}</div>

                <div :class="`w-24 h-24 rounded-lg flex items-center justify-center mb-10 bg-white shadow-2xl group-hover:rotate-[15deg] transition-all duration-500` ">
                    <i :class="`pi ${service.icon} text-4xl text-${service.color}-600` "></i>
                </div>

                <h4 class="text-3xl font-black text-slate-900 mb-6 leading-tight">{{ service.title }}</h4>
                <p class="text-slate-500 leading-relaxed mb-10 font-medium text-lg italic opacity-80 group-hover:opacity-100 transition-opacity">{{ service.desc }}</p>

                <ul class="space-y-4 mb-12">
                    <li v-for="feat in service.features" :key="feat" class="flex items-center gap-4 text-sm font-black text-slate-700">
                        <div class="w-6 h-6 rounded-full bg-red-50 flex items-center justify-center border border-red-100">
                            <i class="pi pi-check text-[10px] text-[#EE1C25]"></i>
                        </div>
                        {{ feat }}
                    </li>
                </ul>

                <button class="w-full py-6 rounded-3xl bg-slate-900 text-white font-black group-hover:bg-[#003399] transition-all shadow-xl flex items-center justify-center gap-3">
                    Demander un devis professionnel <i class="pi pi-arrow-right"></i>
                </button>
            </div>
        </div>
      </div>
    </section>

    <!-- --- [SECTION 5] BENTO GRID ACADEMY --- -->
    <section class="py-32 px-6 bg-[#001A4D] relative rounded-lg mx-4 my-10 shadow-inner overflow-hidden" id="academy">

      <!-- Fond Abstrait Digital -->
      <div class="absolute inset-0 opacity-[0.03] pointer-events-none">
        <div class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(#ffffff_2px,transparent_2px)] [background-size:50px_50px]"></div>
      </div>

      <div class="max-w-[1500px] mx-auto relative z-10">
        <div class="flex flex-col lg:flex-row justify-between items-start mb-24 gap-12">
            <div class="max-w-3xl">
                <h2 class="text-red-500 font-black uppercase tracking-[0.5em] text-sm mb-6 flex items-center gap-4">
                    <span class="w-8 h-8 rounded-full bg-red-500/10 flex items-center justify-center"><i class="pi pi-bolt text-[10px]"></i></span>
                    Subnet Academy
                </h2>
                <h3 class="text-5xl md:text-[5.5rem] font-black text-white tracking-tighter leading-[0.9] mb-8">Apprenez avec des <br/> <span class="italic text-blue-400 underline decoration-red-500 decoration-wavy underline-offset-[15px]">Leaders du Numérique</span>.</h3>
                <p class="text-2xl text-blue-100/60 font-medium max-w-2xl leading-relaxed">Formations intensives basées sur des cas réels africains, certifiées par les géants de la technologie.</p>
            </div>
            <div class="flex flex-col gap-4 w-full lg:w-auto">
                <button class="px-12 py-6 bg-white rounded-3xl font-black text-blue-900 hover:bg-red-500 hover:text-white transition-all shadow-2xl flex items-center justify-center gap-4 group">
                    Explorer le catalogue (450+) <i class="pi pi-search group-hover:scale-125 transition-transform"></i>
                </button>
                <div class="flex items-center gap-4 px-6 py-3 bg-white/5 border border-white/10 rounded-lg justify-center">
                    <i class="pi pi-verified text-green-400"></i>
                    <span class="text-white text-xs font-bold uppercase tracking-widest">Accrédité par le Ministère de l'Éducation Tech</span>
                </div>
            </div>
        </div>

        <!-- BENTO LAYOUT -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
            <!-- Main Course Card -->
            <div class="md:col-span-8 bg-gradient-to-br from-[#003399] to-[#001A4D] rounded-lg p-12 relative overflow-hidden group border border-white/10 shadow-2xl min-h-[500px] flex flex-col justify-between">
                <div class="relative z-10">
                    <div class="flex items-center gap-4 mb-10">
                        <span class="bg-red-600 text-white px-6 py-2 rounded-full text-[11px] font-black uppercase tracking-widest shadow-xl shadow-red-500/20">Bootcamp 2024</span>
                        <span class="bg-blue-400/20 text-blue-200 px-6 py-2 rounded-full text-[11px] font-black uppercase tracking-widest">Hybride (Présentiel/Online)</span>
                    </div>
                    <h4 class="text-5xl md:text-6xl font-black text-white mb-8 max-w-2xl leading-[1.1]">Expert en Ingénierie Réseaux & Installation VSAT Stratégique</h4>
                    <p class="text-blue-200 text-xl mb-12 max-w-lg leading-relaxed opacity-80">Devenez un technicien d'élite capable de déployer des backbones internet en conditions extrêmes.</p>
                </div>

                <div class="relative z-10 flex flex-wrap items-center gap-10 border-t border-white/10 pt-10">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-lg bg-white/5 flex items-center justify-center text-red-500 shadow-inner">
                            <i class="pi pi-clock text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-white font-black text-lg">180 Heures</p>
                            <p class="text-blue-200/50 text-xs font-bold uppercase">Durée intensive</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-lg bg-white/5 flex items-center justify-center text-blue-400 shadow-inner">
                            <i class="pi pi-users text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-white font-black text-lg">15 Places</p>
                            <p class="text-blue-200/50 text-xs font-bold uppercase">Par session</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-lg bg-white/5 flex items-center justify-center text-green-400 shadow-inner">
                            <i class="pi pi-briefcase text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-white font-black text-lg">Placement</p>
                            <p class="text-blue-200/50 text-xs font-bold uppercase">Garanti à 85%</p>
                        </div>
                    </div>
                </div>

                <img src="https://images.unsplash.com/photo-1769697705387-591bfddc2861?q=80&w=687&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" class="absolute top-0 right-0 w-full h-full object-cover opacity-20 group-hover:scale-105 transition-transform duration-[5s] pointer-events-none" />
            </div>

            <!-- Side Bento Column -->
            <div class="md:col-span-4 space-y-8">
                <!-- Course Item 1 -->
                <div v-for="c in courses" :key="c.id" class="bg-white/5 backdrop-blur-3xl rounded-lg p-8 border border-white/10 hover:bg-white/10 transition-all cursor-pointer group shadow-2xl relative overflow-hidden">
                    <div class="flex gap-8 items-center relative z-10">
                        <div class="w-20 h-20 rounded-[1.5rem] bg-gradient-to-br from-blue-500/20 to-blue-900/20 flex items-center justify-center text-blue-400 group-hover:bg-red-500 group-hover:text-white group-hover:rotate-12 transition-all duration-500 shadow-2xl">
                            <i :class="`pi ${c.icon} text-3xl` "></i>
                        </div>
                        <div>
                            <h5 class="text-white font-black mb-2 text-xl leading-tight italic">{{ c.title }}</h5>
                            <p class="text-blue-200/40 text-[10px] font-black uppercase tracking-[0.3em]">{{ c.category }}</p>
                        </div>
                    </div>
                    <div class="absolute -right-5 -bottom-5 text-7xl font-black text-white/5 italic opacity-0 group-hover:opacity-100 transition-opacity">IT</div>
                </div>

                <!-- Live Support Card -->
                <div class="bg-gradient-to-r from-[#EE1C25] to-red-700 rounded-lg p-10 text-white flex flex-col justify-between group cursor-pointer hover:scale-[1.02] transition-all shadow-[0_30px_60px_rgba(238,28,37,0.3)] relative overflow-hidden h-[250px]">
                    <div class="relative z-10">
                        <span class="bg-white/20 px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest mb-4 inline-block">Online Now</span>
                        <h6 class="text-3xl font-black leading-tight italic">Prêt à booster votre avenir ?</h6>
                    </div>
                    <div class="relative z-10 flex items-center justify-between">
                        <p class="text-red-100 font-bold">Parler avec un expert</p>
                        <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center text-red-600 shadow-2xl group-hover:rotate-[360deg] transition-all duration-700">
                            <i class="pi pi-whatsapp text-3xl"></i>
                        </div>
                    </div>
                    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/asfalt-dark.png')] opacity-10"></div>
                </div>
            </div>
        </div>
      </div>
    </section>

    <!-- --- [SECTION 6] PROCESSUS - COMMENT ÇA MARCHE --- -->
    <section class="py-32 px-6">
        <div class="max-w-[1500px] mx-auto">
            <div class="text-center mb-24 space-y-4">
                <h2 class="text-[#EE1C25] font-black uppercase tracking-[0.5em] text-sm">Le Parcours Subnet</h2>
                <h3 class="text-6xl font-black text-slate-900 tracking-tighter">Étape par étape vers l'excellence.</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 relative">
                <!-- Ligne de connection -->
                <div class="hidden lg:block absolute top-1/2 left-0 w-full h-1 bg-slate-100 -z-10"></div>

                <div v-for="(step, idx) in steps" :key="step.title" class="text-center group">
                    <div class="w-24 h-24 rounded-full bg-white border-8 border-slate-50 flex items-center justify-center mx-auto mb-8 shadow-xl group-hover:border-red-100 transition-all relative">
                        <span class="text-2xl font-black text-slate-900">{{ idx + 1 }}</span>
                        <div class="absolute inset-0 rounded-full border-2 border-dashed border-red-500 animate-spin-slow opacity-0 group-hover:opacity-100"></div>
                    </div>
                    <h5 class="text-xl font-black mb-4 text-slate-800">{{ step.title }}</h5>
                    <p class="text-slate-500 font-medium leading-relaxed">{{ step.desc }}</p>
                </div>
            </div>
        </div>
    </section>
    <section class="py-32 px-6 bg-slate-50" id="academy-courses">
      <div class="max-w-[1500px] mx-auto">

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-8">
          <div class="max-w-2xl">
            <h2 class="text-[#EE1C25] font-black uppercase tracking-[0.4em] text-sm mb-4">Formations en Direct</h2>
            <h3 class="text-5xl font-black text-slate-900 tracking-tighter">Développez vos compétences en <span class="text-[#003399]">Temps Réel</span>.</h3>
          </div>
          <div class="flex gap-2 bg-white p-2 rounded-2xl shadow-sm border border-slate-100">
            <button v-for="cat in ['Tous', 'Réseaux', 'Sécurité', 'Cloud']" :key="cat"
                    @click="selectedCategory = cat"
                    :class="['px-6 py-3 rounded-xl font-black text-sm transition-all', selectedCategory === cat ? 'bg-[#003399] text-white' : 'text-slate-500 hover:bg-slate-50']">
              {{ cat }}
            </button>
          </div>
        </div>

        <!-- Grid des Cours -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
          <div v-for="course in filteredCourses" :key="course.id"
               class="group bg-white rounded-lg overflow-hidden border border-slate-100 shadow-sm hover:shadow-2xl transition-all duration-500 flex flex-col h-full">

            <!-- Image & Badge Live -->
            <div class="relative h-56 overflow-hidden">
              <img :src="course.image" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" />
              <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>

              <!-- INDICATEUR LIVE -->
              <div v-if="course.isLive" class="absolute top-6 left-6 flex items-center gap-2 bg-white/95 backdrop-blur-md px-4 py-2 rounded-full shadow-xl">
                <span class="flex h-2 w-2 rounded-full bg-red-600 animate-pulse"></span>
                <span class="text-[10px] font-black text-red-600 uppercase tracking-widest">En Direct</span>
              </div>

              <div class="absolute bottom-6 left-6">
                <span class="px-3 py-1 bg-blue-600 text-white text-[9px] font-black rounded-lg uppercase tracking-widest">{{ course.category }}</span>
              </div>
            </div>

            <!-- Contenu -->
            <div class="p-8 flex flex-col flex-1">
              <div class="flex items-center gap-2 mb-4">
                <img :src="course.instructorAvatar" class="w-6 h-6 rounded-full" />
                <span class="text-xs font-bold text-slate-400">Par {{ course.instructor }}</span>
              </div>
              <h4 class="text-xl font-black text-slate-900 mb-4 leading-tight group-hover:text-[#003399] transition-colors">{{ course.title }}</h4>

              <div class="flex items-center justify-between mt-auto pt-6 border-t border-slate-50">
                <div class="flex items-center gap-4">
                   <div class="flex items-center gap-1 text-amber-400">
                     <i class="pi pi-star-fill text-xs"></i>
                     <span class="text-slate-900 font-black text-xs">{{ course.rating }}</span>
                   </div>
                   <div class="flex items-center gap-1 text-slate-400">
                     <i class="pi pi-clock text-xs"></i>
                     <span class="text-xs font-bold">{{ course.duration }}</span>
                   </div>
                </div>
              </div>

              <!-- BOUTON D'ACTION DYNAMIQUE -->
              <a v-if="course.isLive" :href="course.liveLink" target="_blank"
                 class="mt-6 w-full py-4 bg-red-600 text-white rounded-2xl font-black text-sm flex items-center justify-center gap-3 hover:bg-red-700 shadow-lg shadow-red-100 transition-all">
                Suivre la session <i class="pi pi-video"></i>
              </a>
              <button v-else class="mt-6 w-full py-4 bg-slate-900 text-white rounded-2xl font-black text-sm flex items-center justify-center gap-3 hover:bg-[#003399] transition-all">
                Détails du cours <i class="pi pi-arrow-right"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Banner Academy Promo -->
        <div class="mt-20 bg-[#003399] rounded-[3rem] p-12 relative overflow-hidden flex flex-col lg:flex-row items-center justify-between gap-10">
          <div class="relative z-10 text-center lg:text-left">
            <h4 class="text-3xl md:text-4xl font-black text-white mb-4 italic">Devenez instructeur certifié Subnet</h4>
            <p class="text-blue-100 font-medium text-lg">Partagez votre expertise avec des milliers d'apprenants en RDC.</p>
          </div>
          <button class="relative z-10 px-12 py-5 bg-[#EE1C25] text-white rounded-2xl font-black text-lg shadow-2xl hover:scale-105 transition-all">Publier un cours</button>
          <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl"></div>
        </div>

      </div>
    </section>
    <!-- --- [SECTION 7] PHILOSOPHIE & ENGAGEMENT RDC --- -->
    <section class="py-32 px-6 relative overflow-hidden bg-slate-50/50">
        <!-- Decoration -->
        <div class="absolute -bottom-20 -right-20 w-[500px] h-[500px] bg-red-100/50 rounded-full blur-3xl"></div>

        <div class="max-w-[1500px] mx-auto grid grid-cols-1 lg:grid-cols-2 gap-24 items-center">
            <div class="relative">
                <!-- Stack d'images complexes -->
                <div class="grid grid-cols-2 gap-6 relative z-10">
                    <div class="space-y-6">
                        <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=2070" class="rounded-lg shadow-2xl h-[400px] w-full object-cover transform hover:-rotate-3 transition-transform duration-700" />
                        <div class="bg-[#003399] p-10 rounded-lg text-white shadow-2xl">
                            <p class="text-5xl font-black italic tracking-tighter mb-2">10 Ans</p>
                            <p class="text-xs font-black uppercase tracking-[0.3em] opacity-70 leading-relaxed">Au service de la transformation digitale du Congo.</p>
                        </div>
                    </div>
                    <div class="pt-12 space-y-6">
                        <div class="bg-[#EE1C25] p-10 rounded-lg text-white shadow-2xl transform rotate-3">
                            <i class="pi pi-heart-fill text-4xl mb-6"></i>
                            <p class="text-xl font-black leading-tight">Fièrement établi à Goma & Goma.</p>
                        </div>
                        <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?q=80&w=2070" class="rounded-lg shadow-2xl h-[450px] w-full object-cover transform hover:rotate-3 transition-transform duration-700" />
                    </div>
                </div>
                <!-- Badge Flottant -->
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-48 h-48 bg-white rounded-full border-[15px] border-slate-50 flex items-center justify-center shadow-2xl z-20">
                    <img src="https://subnetcongo.net/assets/img/logo.png" class="w-24 opacity-80" />
                </div>
            </div>

            <div class="space-y-12 relative z-10">
                <div class="space-y-6">
                    <h2 class="text-[#EE1C25] font-black uppercase tracking-[0.5em] text-sm italic">Notre Mission Panafricaine</h2>
                    <h3 class="text-5xl md:text-[5.5rem] font-black text-slate-900 leading-[0.9] tracking-tighter">Pas de digital sans infrastructure, pas d'infrastructure sans <span class="text-[#003399] italic">maîtrise</span>.</h3>
                    <p class="text-2xl text-slate-500 leading-relaxed font-medium">
                        SubnetCongo SARL est le pont entre l'accès au réseau mondial et la compétence technologique locale. Nous ne vendons pas juste des mégaoctets, nous créons des experts.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="point in ['Installation Antennes (VSAT/Starlink)', 'Déploiement Fibre Optique Goma', 'Centre de Formation Certifiant Cisco', 'Support Technique 24/7 de proximité', 'Audit de Sécurité des SI', 'Expertise Cloud & Datacenter']"
                         :key="point" class="flex items-center gap-6 bg-white p-7 rounded-3xl border border-slate-100 group hover:border-red-200 transition-all shadow-sm">
                        <div class="w-12 h-12 bg-slate-50 rounded-xl flex items-center justify-center text-[#EE1C25] group-hover:bg-[#EE1C25] group-hover:text-white transition-all shadow-inner">
                            <i class="pi pi-check-circle text-lg"></i>
                        </div>
                        <span class="text-sm font-black text-slate-700 uppercase tracking-tight">{{ point }}</span>
                    </div>
                </div>

                <div class="pt-10 flex flex-wrap gap-6">
                    <button class="bg-slate-900 text-white px-12 py-7 rounded-3xl font-black text-xl hover:bg-[#003399] transition-all flex items-center gap-6 group shadow-2xl shadow-slate-300">
                        Parler à un de nos Experts <i class="pi pi-phone-at-point animate-bounce"></i>
                    </button>
                    <button class="bg-white border-4 border-slate-900 px-12 py-7 rounded-3xl font-black text-xl hover:bg-slate-900 hover:text-white transition-all shadow-xl">
                        Nos Certifications
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- --- [SECTION 8] PARTENAIRES --- -->
    <section class="py-20 border-y border-slate-100 bg-white overflow-hidden">
        <div class="max-w-[1500px] mx-auto px-6">
            <p class="text-center text-[10px] font-black uppercase tracking-[0.5em] text-slate-400 mb-12 italic">Ils nous font confiance à travers le monde</p>
            <div class="flex flex-wrap justify-center items-center gap-16 md:gap-32 grayscale hover:grayscale-0 transition-all opacity-40 hover:opacity-100">
                <img v-for="i in 5" :key="i" :src="`https://placehold.co/200x80/white/003399?text=PARTENAIRE+${i}`" class="h-10 md:h-12 w-auto object-contain" />
            </div>
        </div>
    </section>

    <!-- --- [SECTION 9] FAQ --- -->
    <section class="py-32 px-6">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-20 space-y-4">
                <h2 class="text-red-500 font-black uppercase tracking-widest text-xs">Questions Fréquentes</h2>
                <h3 class="text-5xl font-black text-slate-900 tracking-tighter">Tout ce que vous devez savoir.</h3>
            </div>

            <div class="space-y-6">
                <div v-for="(item, idx) in faqs" :key="idx" class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden transition-all">
                    <button @click="activeFaq = activeFaq === idx ? null : idx" class="w-full p-8 flex items-center justify-between text-left hover:bg-slate-50 transition-colors">
                        <span class="text-xl font-black text-slate-800">{{ item.q }}</span>
                        <i :class="['pi text-[#EE1C25] transition-transform', activeFaq === idx ? 'pi-minus rotate-180' : 'pi-plus']"></i>
                    </button>
                    <div v-show="activeFaq === idx" class="p-8 pt-0 text-slate-500 font-medium leading-relaxed border-t border-slate-50">
                        {{ item.a }}
                    </div>
                </div>
            </div>
        </div>
    </section>

<footer class="relative bg-[#0A0F1C] pt-24 pb-10 px-6 overflow-hidden">

    <!-- Ornements de fond (Discrets et Modernes) -->
    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-[#EE1C25] to-transparent opacity-30"></div>
    <div class="absolute -top-24 -left-24 w-64 h-64 bg-[#003399]/20 rounded-full blur-[100px]"></div>
    <div class="absolute bottom-0 right-0 w-80 h-80 bg-[#EE1C25]/10 rounded-full blur-[120px]"></div>

    <div class="max-w-[1400px] mx-auto relative z-10">

      <!-- [1] LA CARTE NEWSLETTER (Design Flottant) -->
      <div class="relative mb-20 p-4 md:p-8 rounded-lg bg-gradient-to-br from-[#161C2D] to-[#0A0F1C] border border-white/10 shadow-2xl overflow-hidden group">
        <div class="flex flex-col lg:flex-row items-center justify-between gap-10">
          <div class="text-center lg:text-left space-y-1">
            <h4 class="text-2xl md:text-4xl font-black text-white tracking-tighter leading-none">
              Restez à la pointe de la <span class="text-[#EE1C25]">Tech en RDC</span>.
            </h4>
            <p class="text-slate-400 font-medium text-base md:text-lg max-w-lg">
              Rejoignez notre communauté pour recevoir les alertes sur nos nouvelles couvertures Starlink et nos bourses d'études.
            </p>
          </div>

          <div class="w-full lg:w-auto flex flex-col sm:flex-row gap-3">
            <input type="email" placeholder="votre@email.com"
                   class="w-full sm:w-[300px] bg-white/5 border border-white/10 rounded-2xl px-6 py-5 text-white focus:bg-white/10 focus:border-[#EE1C25] outline-none transition-all font-bold placeholder:text-slate-500 shadow-inner" />
            <button class="bg-[#EE1C25] text-white px-10 py-5 rounded-2xl font-black hover:scale-105 active:scale-95 transition-all shadow-lg shadow-[#EE1C25]/20 flex items-center justify-center gap-3">
              S'abonner <i class="pi pi-send text-sm"></i>
            </button>
          </div>
        </div>
        <!-- Décoration interne -->
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"></div>
      </div>

      <!-- [2] LA GRILLE PRINCIPALE (Adaptative Mobile) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8 mb-20 text-center sm:text-left">

        <!-- Marque & Bio -->
        <div class="space-y-8 flex flex-col items-center sm:items-start">
          <img src="https://subnetcongo.net/assets/img/logo.png" class="h-12 w-auto brightness-200" alt="SubnetCongo" />
          <p class="text-slate-400 font-medium leading-relaxed italic text-sm md:text-base">
            Leader panafricain basé à Goma, combinant infrastructure réseau et centre de formation d'élite.
          </p>
          <!-- Réseaux Sociaux (Gros boutons tactiles pour mobile) -->
          <div class="flex gap-3">
            <a v-for="social in ['facebook', 'linkedin', 'instagram', 'whatsapp']" :key="social" href="#"
               class="w-11 h-11 bg-white/5 rounded-xl flex items-center justify-center text-slate-300 hover:bg-[#003399] hover:text-white transition-all border border-white/5">
              <i :class="[`pi pi-${social}`]"></i>
            </a>
          </div>
        </div>

        <!-- Liens Solutions -->
        <div class="space-y-6">
          <h5 class="text-white font-black uppercase text-xs tracking-widest border-b border-[#EE1C25] w-fit mx-auto sm:mx-0 pb-2">Solutions</h5>
          <ul class="space-y-4">
            <li v-for="link in footerLinks[0].links" :key="link">
              <a href="#" class="text-slate-400 font-bold hover:text-[#EE1C25] transition-colors flex items-center justify-center sm:justify-start gap-2 group">
                <span class="w-1 h-1 bg-slate-700 rounded-full group-hover:bg-[#EE1C25]"></span> {{ link }}
              </a>
            </li>
          </ul>
        </div>

        <!-- Liens Academy -->
        <div class="space-y-6">
          <h5 class="text-white font-black uppercase text-xs tracking-widest border-b border-[#003399] w-fit mx-auto sm:mx-0 pb-2">Academy</h5>
          <ul class="space-y-4">
            <li v-for="link in footerLinks[1].links" :key="link">
              <a href="#" class="text-slate-400 font-bold hover:text-[#003399] transition-colors flex items-center justify-center sm:justify-start gap-2 group">
                <span class="w-1 h-1 bg-slate-700 rounded-full group-hover:bg-[#003399]"></span> {{ link }}
              </a>
            </li>
          </ul>
        </div>

        <!-- Certifications & Siège (Style "Badge") -->
        <div class="space-y-8 flex flex-col items-center sm:items-start">
          <div class="w-full bg-white/5 p-6 rounded-3xl border border-white/10 space-y-4">
             <div class="flex items-center gap-4">
                <i class="pi pi-verified text-xl text-[#EE1C25]"></i>
                <div class="text-left">
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Starlink</p>
                  <p class="text-white font-bold text-xs uppercase">Authorized Reseller</p>
                </div>
             </div>
             <div class="flex items-center gap-4">
                <i class="pi pi-shield text-xl text-[#003399]"></i>
                <div class="text-left">
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Licence FAI</p>
                  <p class="text-white font-bold text-xs uppercase">Certifié par l'ARPTC</p>
                </div>
             </div>
          </div>
          <div class="text-sm">
             <p class="text-white font-black mb-2 uppercase tracking-tighter italic">Nord-Kivu / Goma</p>
             <p class="text-slate-500 font-bold leading-tight">N° 0001, Avenue Des Touristes, Quartier les Volcans, Commune de Goma, Ville de Goma, Nord-Kivu</p>
          </div>
        </div>
      </div>

      <!-- [3] LIGNE FINALE (COPYRIGHT) -->
      <div class="pt-8 border-t border-white/5 flex flex-col md:flex-row justify-between items-center gap-6">
        <div class="flex flex-col md:flex-row items-center gap-3 text-center md:text-left">
           <p class="text-slate-500 font-black text-[10px] uppercase tracking-[0.3em]">
             © 2026 <span class="text-white">SUBNETCONGO</span>.
           </p>
           <span class="hidden md:block text-slate-800">|</span>
           <p class="text-slate-600 font-bold text-[10px] uppercase">Design By Linux99</p>
        </div>

        <div class="flex items-center gap-6 text-[9px] font-black uppercase tracking-widest text-slate-500">
          <a href="#" class="hover:text-[#EE1C25] transition-colors">Confidentialité</a>
          <a href="#" class="hover:text-[#EE1C25] transition-colors">Mentions</a>
          <!-- <a href="#" class="hover:text-[#EE1C25] transition-colors">CGV</a> -->
        </div>
      </div>
    </div>
  </footer>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * --- ÉTATS RÉACTIFS ---
 */
const scrolled = ref(false);
const isMenuOpen = ref(false);
const login = () => {
    router.get(route('login'));
};


const activeSlide = ref(0);
const activeFaq = ref(null);

/**
 * --- DONNÉES DE NAVIGATION ---
 */
const navLinks = [
    { name: 'Acceuil', href: '#solutions', icon: 'pi-globe' },
    { name: 'Apropos', href: '#', icon: 'pi-building' },
    { name: 'Academy', href: '#academy', icon: 'pi-graduation-cap', submenu: ['Certifications Cisco', 'Cybersécurité', 'Cloud Computing', 'Développement Web'] },
    { name: 'Génie Civil', href: '#', icon: 'pi-cog' },
    { name: 'Partenariats', href: '#', icon: 'pi-users' },
    { name: 'Contact', href: '#', icon: 'pi-headphones' }
];

/**
 * --- DONNÉES DU CAROUSEL HERO ---
 */
const heroSlides = [
    {
        id: 1,
        badge: "Leader Connectivité RDC",
        title: "Désenclaver le",
        highlight: "Congo par le Web.",
        description: "Déploiement stratégique d'antennes VSAT et solutions Starlink pour les zones minières et forestières reculées. Une latence record pour une productivité sans limites.",
        bgImage: "https://images.unsplash.com/photo-1544652478-6653e09f18a2?q=80&w=2070",
        mainImage: "https://images.unsplash.com/photo-1573166364524-d9dbfd8bbf83?q=80&w=2069",
        icon: "pi-map-marker",
        cardTitle: "Omniprésence Nationale",
        cardSub: "Couverture 26 provinces garantie."
    },
    {
        id: 2,
        badge: "Élite Technologique",
        title: "Façonner les",
        highlight: "Experts de Demain.",
        description: "Plongez dans un écosystème d'apprentissage de classe mondiale. De la certification Cisco CCNA à l'Audit de Sécurité, boostez votre carrière internationale.",
        bgImage: "https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=2070",
        mainImage: "https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=2070",
        icon: "pi-book",
        cardTitle: "Certification Cisco Pro",
        cardSub: "92% de taux de réussite aux examens."
    },
    {
        id: 3,
        badge: "Souveraineté Numérique",
        title: "Bâtir les",
        highlight: "Routes du Digital.",
        description: "Maintenance critique, tirage de fibre optique noire et ingénierie de réseaux complexes pour les sièges sociaux et institutions gouvernementales.",
        bgImage: "https://images.unsplash.com/photo-1558494949-ef010911182e?q=80&w=2070",
        mainImage: "https://images.unsplash.com/photo-1551703599-6b3e8379aa8c?q=80&w=2070",
        icon: "pi-cog",
        cardTitle: "Uptime 99.99%",
        cardSub: "Monitoring NOC en temps réel."
    }
];

/**
 * --- SERVICES ISP ---
 */
const ispServices = [
    {
        title: "VSAT & Satellite Elite",
        icon: "pi-globe",
        color: "blue",
        desc: "L'accès ultime pour les sites isolés. Forfaits illimités adaptés aux besoins industriels et humanitaires.",
        features: ["Bandes Ku, Ka & C", "Priorisation du trafic (QoS)", "Installation en moins de 48h"]
    },
    {
        title: "Starlink Business Pro",
        icon: "pi-bolt",
        color: "red",
        desc: "Vitesse révolutionnaire jusqu'à 350 Mbps. Nous sommes votre installateur certifié pour une stabilité maximale.",
        features: ["Kits Haute Performance", "Support technique local", "Facturation RDC simplifiée"]
    },
    {
        title: "Fibre Noire Dédiée",
        icon: "pi-sitemap",
        color: "blue",
        desc: "Connexions symétriques Giga-bit pour les datacenters et entreprises multisites à Goma.",
        features: ["Latence < 10ms", "Monitoring 24/7/365", "Garantie Temps de Rétablissement"]
    }
];

/**
 * --- COURS ACADÉMIE ---
 */
const courses = [
    { id: 1, title: "Administrateur Cisco CCNA & CCNP", category: "Réseaux & Protocoles", icon: "pi-database" },
    { id: 2, title: "Audit de Sécurité & Ethical Hacking", category: "Cyber-Défense", icon: "pi-lock" },
    { id: 3, title: "Cloud Architect AWS & Azure", category: "Infrastructure Cloud", icon: "pi-cloud" },
    { id: 4, title: "Ingénierie de Télécoms Satellitaires", category: "Telecom Expert", icon: "pi-wifi" }
];

/**
 * --- STATISTIQUES ---
 */
const statistics = [
    { value: "15K+", label: "Apprenants certifiés" },
    { value: "850+", label: "Sites VSAT actifs" },
    { value: "24/7", label: "Support Technique" },
    { value: "10+", label: "Années d'Expérience" }
];

/**
 * --- ÉTAPES DU PROCESSUS ---
 */
const steps = [
    { title: "Consultation", desc: "Analyse de vos besoins spécifiques par nos ingénieurs." },
    { title: "Déploiement", desc: "Installation rigoureuse par nos équipes mobiles partout en RDC." },
    { title: "Formation", desc: "Transfert de compétences pour une autonomie totale." },
    { title: "Maintenance", desc: "Suivi proactif et assistance immédiate en cas d'alerte." }
];

/**
 * --- FAQ DATA ---
 */
const faqs = [
    { q: "Proposez-vous Starlink partout en RDC ?", a: "Oui, nous couvrons l'intégralité du territoire national, des zones urbaines aux mines les plus isolées." },
    { q: "Les formations sont-elles reconnues ?", a: "Nos formations sont certifiantes et alignées sur les standards internationaux (Cisco, CompTIA, etc.)." },
    { q: "Quels sont vos délais d'intervention ?", a: "Pour les urgences critiques, nos équipes peuvent intervenir sous 4 à 12 heures selon la zone géographique." },
    { q: "Offrez-vous des bourses d'études ?", a: "Chaque année, SubnetCongo offre 50 bourses aux jeunes talents congolais méritants." }
];

/**
 * --- LIENS FOOTER ---
 */
const footerLinks = [
    {
        title: "Solutions Connectées",
        links: ["Internet par Satellite", "Starlink for Business", "Fibre Optique FTTH/FTTB", "Audit & Consulting Réseaux", "Hosting & Cloud Backup"]
    },
    {
        title: "Subnet Academy",
        links: ["Catalogue Complet", "Certifications Cisco", "Sécurité des SI", "Développement d'Apps", "Espace Recrutement"]
    },
    {
        title: "Entreprise",
        links: ["Qui sommes-nous ?", "Carrières IT", "Actualités Tech", "Impact Social", "Contactez-nous"]
    }
];
// --- DONNÉES DES COURS (AVEC LIVE) ---
const allCourses = ref([
    {
        id: 1,
        title: "Installation & Optimisation Starlink Business",
        category: "Réseaux",
        instructor: "Eng. Marc L.",
        instructorAvatar: "https://i.pravatar.cc/150?u=marc",
        image: "https://images.unsplash.com/photo-1517433456452-f9633a875f6f?q=80&w=2070",
        rating: 4.9,
        duration: "12h",
        isLive: true,
        liveLink: "https://zoom.us/subnet-live-session-1"
    },
    {
        id: 2,
        title: "Sécurité des SI : Audit et Pentesting",
        category: "Sécurité",
        instructor: "Sarah K.",
        instructorAvatar: "https://i.pravatar.cc/150?u=sarah",
        image: "https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=2070",
        rating: 4.8,
        duration: "45h",
        isLive: false
    },
    {
        id: 3,
        title: "Administration Cisco CCNA (Module 1)",
        category: "Réseaux",
        instructor: "Jean-Paul M.",
        instructorAvatar: "https://i.pravatar.cc/150?u=jp",
        image: "https://images.unsplash.com/photo-1558494949-ef010911182e?q=80&w=2070",
        rating: 4.7,
        duration: "60h",
        isLive: true,
        liveLink: "https://youtube.com/live/subnet-cisco"
    },
    {
        id: 4,
        title: "Architecture Cloud AWS pour Entreprises",
        category: "Cloud",
        instructor: "Ali B.",
        instructorAvatar: "https://i.pravatar.cc/150?u=ali",
        image: "https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=2072",
        rating: 5.0,
        duration: "30h",
        isLive: false
    }
]);
const selectedCategory = ref('Tous');

const filteredCourses = computed(() => {
    if (selectedCategory.value === 'Tous') return allCourses.value;
    return allCourses.value.filter(c => c.category === selectedCategory.value);
});

// const ispServices = [
//     { title: "Satellite VSAT", icon: "pi-globe", desc: "Internet haut débit pour les sites les plus reculés." },
//     { title: "Starlink Business", icon: "pi-bolt", desc: "Installation agréée et support local dédié." },
//     { title: "Fibre Optique", icon: "pi-sitemap", desc: "Liaisons symétriques pour entreprises exigeantes." }
// ];

// const handleScroll = () => { scrolled.value = window.scrollY > 80; };

let timer;
onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    timer = setInterval(() => { activeSlide.value = (activeSlide.value + 1) % heroSlides.length; }, 8000);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    clearInterval(timer);
});
/**
 * --- LOGIQUE DES ÉVÉNEMENTS ---
 */
let carouselTimer;

const handleScroll = () => {
    scrolled.value = window.scrollY > 80;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);

    // Rotation automatique du carousel toutes les 8 secondes
    carouselTimer = setInterval(() => {
        activeSlide.value = (activeSlide.value + 1) % heroSlides.length;
    }, 8000);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    clearInterval(carouselTimer);
});
</script>

<style>
/* --- IMPORTATION DE LA POLICE --- */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

/* --- CONFIGURATION GÉNÉRALE --- */
:root {
  scroll-behavior: smooth;
}

body {
    font-family: 'Inter', sans-serif;
    background-color: #FDFFFF;
    -webkit-font-smoothing: antialiased;
}

/* --- ANIMATIONS DU DRAWER (À GAUCHE) --- */
.drawer-left-enter-active, .drawer-left-leave-active {
  transition: transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
}
.drawer-left-enter-from, .drawer-left-leave-to {
  transform: translateX(-100%);
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

/* --- ANIMATIONS DU CAROUSEL --- */
.fade-bg-enter-active, .fade-bg-leave-active {
  transition: opacity 2s ease-in-out;
}
.fade-bg-enter-from, .fade-bg-leave-to {
  opacity: 0;
}

.slide-fade-content-enter-active, .slide-fade-content-leave-active {
  transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-fade-content-enter-from {
  opacity: 0;
  transform: translateY(40px) scale(0.95);
}
.slide-fade-content-leave-to {
  opacity: 0;
  transform: translateY(-40px) scale(1.05);
}

.image-swap-enter-active, .image-swap-leave-active {
  transition: all 1.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.image-swap-enter-from {
  clip-path: inset(0 0 0 100%);
}
.image-swap-leave-to {
  clip-path: inset(0 100% 0 0);
}

.slide-up-card-enter-active, .slide-up-card-leave-active {
  transition: all 0.8s ease;
}
.slide-up-card-enter-from {
  opacity: 0;
  transform: translateY(60px);
}

/* --- EFFETS ET KEYFRAMES --- */
@keyframes float {
    0% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-30px) rotate(1deg); }
    100% { transform: translateY(0px) rotate(0deg); }
}

@keyframes slow-zoom {
    0% { transform: scale(1); }
    100% { transform: scale(1.25); }
}

@keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.animate-float {
    animation: float 8s ease-in-out infinite;
}

.animate-slow-zoom {
    animation: slow-zoom 25s linear infinite alternate;
}

.animate-spin-slow {
    animation: spin-slow 10s linear infinite;
}

/* --- SCROLLBAR CUSTOM --- */
::-webkit-scrollbar {
    width: 10px;
}
::-webkit-scrollbar-track {
    background: #f8fafc;
}
::-webkit-scrollbar-thumb {
    background: #003399;
    border-radius: 20px;
    border: 2px solid #f8fafc;
}
::-webkit-scrollbar-thumb:hover {
    background: #EE1C25;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

/* --- UTILS --- */
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.text-stroke {
    -webkit-text-stroke: 1px rgba(255,255,255,0.2);
    color: transparent;
}
</style>
