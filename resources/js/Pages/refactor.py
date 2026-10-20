import re
import os

files = [
    r'c:\Users\Daniel\Documents\2025 - Personnel\Projets\SUBNETCONGO\elearning\resources\js\Pages\Dashboard.vue',
    r'c:\Users\Daniel\Documents\2025 - Personnel\Projets\SUBNETCONGO\elearning\resources\js\Pages\DashboardS.vue'
]

def map_colors(content):
    content = re.sub(r'indigo-(\d+)', r'blue-\1', content)
    content = re.sub(r'violet-(\d+)', r'blue-\1', content)
    content = re.sub(r'sky-(\d+)', r'blue-\1', content)
    
    content = re.sub(r'emerald-(\d+)', r'red-\1', content)
    content = re.sub(r'amber-(\d+)', r'red-\1', content)
    content = re.sub(r'rose-(\d+)', r'red-\1', content)
    return content

with open(files[0], 'r', encoding='utf-8') as f:
    admin_content = f.read()

admin_header_pattern = r'<!-- --- \[SECTION 1\] TOP COMMAND CENTER --- -->.*?<!-- --- \[SECTION 2\] MACRO KPI GRID --- -->'
admin_new_header = r'''<!-- --- [SECTION 1] TOP COMMAND CENTER --- -->
            <div class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm backdrop-blur-md bg-white/80">
                <div class="max-w-[1800px] mx-auto px-6 py-4 flex flex-col lg:flex-row justify-between items-center gap-6">

                    <div class="flex items-center gap-5">
                        <div class="w-14 h-14 bg-gradient-to-tr from-blue-600 to-blue-800 rounded-xl flex items-center justify-center shadow-xl shadow-blue-100 animate-pulse-slow">
                            <i class="pi pi-chart-bar text-white text-2xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Gouvernance SNC</span>
                                <Badge :value="props.meta.version" severity="info" class="text-[9px] h-4 !bg-blue-600 !border-blue-600" />
                            </div>
                            <h1 class="text-2xl font-black text-slate-800 tracking-tight italic">{{ props.meta.greeting }}</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search text-slate-400" />
                            <InputText v-model="activeFilters.search" @input="applyDeepFilters" placeholder="Rechercher une cohorte..." class="w-64 bg-slate-50 border-none rounded-xl font-bold focus:ring-blue-500" />
                        </IconField>

                        <div class="h-10 w-px bg-slate-200 mx-2"></div>

                        <Button icon="pi pi-filter" label="Filtres" class="p-button-outlined p-button-secondary rounded-xl font-black" @click="showFilterSidebar = true" />
                        <Button icon="pi pi-cloud-download" label="Intelligence Export" class="!bg-blue-600 hover:!bg-blue-700 !border-none rounded-xl font-black shadow-lg shadow-blue-100 !text-white" @click="showExportModal = true" />
                        <Button icon="pi pi-refresh" class="p-button-secondary p-button-text rounded-xl" @click="purgeCache" v-tooltip.bottom="'Forcer le recalcul'" />
                    </div>
                </div>
            </div>

            <!-- --- [SECTION 2] MACRO KPI GRID --- -->'''

admin_content = re.sub(admin_header_pattern, admin_new_header, admin_content, flags=re.DOTALL)
admin_content = map_colors(admin_content)

with open(files[0], 'w', encoding='utf-8') as f:
    f.write(admin_content)

with open(files[1], 'r', encoding='utf-8') as f:
    student_content = f.read()

student_header_pattern = r'<!-- ═══════════════════════════════════════════════════\s*HEADER GLOBAL — DARK ENTERPRISE\s*═══════════════════════════════════════════════════ -->.*?<!-- ═══════════════════════════════════════════════════\s*MAIN — WHITE FLOATING CARD\s*═══════════════════════════════════════════════════ -->'
student_new_header = r'''<!-- ═══════════════════════════════════════════════════
                 HEADER GLOBAL — ULTRA PRO V50 UI
            ═══════════════════════════════════════════════════ -->
            <div class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm backdrop-blur-md bg-white/80">
                <div class="max-w-[1800px] mx-auto px-6 py-4 flex flex-col lg:flex-row justify-between items-center gap-6">

                    <div class="flex items-center gap-5">
                        <div class="relative">
                            <Avatar
                                :label="auth?.user?.name?.charAt(0)?.toUpperCase()"
                                :image="auth?.user?.profile_photo_url"
                                shape="circle"
                                size="large"
                                class="w-14 h-14 border-2 border-white/10 ring-2 ring-blue-500/30 bg-gradient-to-br from-blue-600 to-blue-800 text-white text-2xl font-black shadow-xl shadow-blue-100 animate-pulse-slow"
                            />
                            <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-red-500 border-2 border-white rounded-full" aria-hidden="true"></span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Espace Apprenant</span>
                                <Tag value="V50" class="!bg-blue-600 !text-white !border-none !text-[9px] !font-black !uppercase !tracking-widest" />
                            </div>
                            <h1 class="text-2xl font-black text-slate-800 tracking-tight italic">
                                {{ meta?.greeting || 'Bonjour' }}, {{ auth?.user?.name?.split(' ')[0] }} <span class="inline-block animate-pulse">👋</span>
                            </h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 flex-wrap">
                        <Link :href="route('student.my-courses')" class="px-4 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 text-xs font-bold uppercase tracking-widest flex items-center gap-2 transition-colors">
                            <i class="pi pi-book text-sm text-blue-600"></i> Mes cours
                        </Link>
                        <Link :href="route('exams.learner.index')" class="px-4 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 text-xs font-bold uppercase tracking-widest flex items-center gap-2 transition-colors">
                            <i class="pi pi-pencil text-sm text-red-500"></i> Examens
                        </Link>
                        <Link :href="route('student.certificates')" class="px-4 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 text-xs font-bold uppercase tracking-widest flex items-center gap-2 transition-colors">
                            <i class="pi pi-verified text-sm text-blue-600"></i> Certificats
                        </Link>
                        
                        <div class="h-10 w-px bg-slate-200 mx-2"></div>

                        <div class="flex items-stretch bg-slate-50 rounded-xl overflow-hidden border border-slate-200 font-mono text-sm">
                            <div class="flex items-center gap-2 px-3.5 py-2 border-r border-slate-200" v-tooltip.bottom="'Points d\'expérience'">
                                <i class="pi pi-bolt text-red-500 text-xs"></i>
                                <span class="text-slate-800 font-bold">{{ stats?.xp ?? 0 }}</span>
                                <span class="text-slate-400 text-[11px]">xp</span>
                            </div>
                            <div class="flex items-center gap-2 px-3.5 py-2" v-tooltip.bottom="'Jours consécutifs'">
                                <i class="pi pi-calendar text-blue-600 text-xs"></i>
                                <span class="text-slate-800 font-bold">{{ stats?.streak ?? 0 }}</span>
                                <span class="text-slate-400 text-[11px]">jours</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════
                 MAIN CONTENT
            ═══════════════════════════════════════════════════ -->
            <div class="max-w-[1800px] mx-auto px-6 mt-10 relative z-20">'''

student_content = re.sub(student_header_pattern, student_new_header, student_content, flags=re.DOTALL)
student_content = re.sub(r'<div class="max-w-\[1600px\] mx-auto px-4 lg:px-6 -mt-10 relative z-20">\s*<div class="bg-white rounded-xl shadow-xl border border-slate-200 p-6 lg:p-8 min-h-\[600px\]">', '', student_content, count=1)
student_content = re.sub(r'</div>\s*</div>\s*</div>\s*<!-- Q&A Dialog -->', '</div>\n<!-- Q&A Dialog -->', student_content)

student_content = map_colors(student_content)

student_content = student_content.replace("'#3E5EFF'", "'#2563EB'")
student_content = student_content.replace("'#2FBF71'", "'#DC2626'")
student_content = student_content.replace("'#F2A93B'", "'#EF4444'")
student_content = student_content.replace("'#10B981'", "'#DC2626'")
student_content = student_content.replace("bg-slate-900", "bg-white border border-slate-200 shadow-sm")
student_content = student_content.replace("text-white", "text-slate-800")

with open(files[1], 'w', encoding='utf-8') as f:
    f.write(student_content)
print('Done!')
