<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO ENTERPRISE - STUDENT INTELLIGENCE HUB
 * ======================================================================================
 */
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SncDialog from '@/Components/SncDialog.vue';
import SncSection from '@/Components/SncSection.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import debounce from 'lodash/debounce';

// PRIMEVUE CORE
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Avatar from 'primevue/avatar';
import Paginator from 'primevue/paginator';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import Tooltip from 'primevue/tooltip';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Menu from 'primevue/menu';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    students: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    courses: { type: Array, default: () => [] },
    stats: { type: Object, required: true },
    availableStatuses: { type: Array, default: () => [] }
});

const isLoading = ref(false);
const displayMode = ref('table');
const showQuickView = ref(false);
const selectedStudent = ref(null);
const selectedStudents = ref([]);

// FILTERS
const activeFilters = ref({
    search: props.filters.search || '',
    course: props.filters.course || null,
    status: props.filters.status || null,
    performance: props.filters.performance || null,
});

const performanceOptions = computed(() => [
    { label: t('pages.student_reports.perfHigh'), value: 'high' },
    { label: t('pages.student_reports.perfMid'), value: 'mid' },
    { label: t('pages.student_reports.perfLow'), value: 'low' },
]);

const syncData = debounce(() => {
    isLoading.value = true;
    router.get(route('student-reports.index'), { ...activeFilters.value, page: 1 }, {
        preserveState: true, preserveScroll: true, replace: true,
        onFinish: () => isLoading.value = false
    });
}, 400);

watch(activeFilters, () => syncData(), { deep: true });

const onPageChange = (event) => {
    isLoading.value = true;
    router.get(props.students.path, { page: event.page + 1, ...activeFilters.value }, {
        preserveState: true, preserveScroll: true, onFinish: () => isLoading.value = false
    });
};

const getScoreSeverity = (s) => s >= 80 ? 'success' : s >= 50 ? 'warn' : 'danger';
const getScoreColor = (s) => s >= 80 ? '#10B981' : s >= 50 ? '#F59E0B' : '#E11D48';
const openQuickView = (s) => { selectedStudent.value = s; showQuickView.value = true; };

// Menu kebab par ligne (mobile)
const rowMenu = ref(null);
const rowMenuTarget = ref(null);
const rowMenuItems = computed(() => {
    const student = rowMenuTarget.value;
    if (!student) return [];

    return [
        { label: t('pages.student_reports.quickView'), icon: 'pi pi-eye', command: () => openQuickView(student) },
        { label: t('pages.student_reports.fullFile'), icon: 'pi pi-arrow-right', command: () => router.visit(route('student-reports.show', student.id)) },
    ];
});
const toggleRowMenu = (event, student) => {
    rowMenuTarget.value = student;
    rowMenu.value?.toggle(event);
};

const headerActions = computed(() => [
    { label: t('pages.student_reports.refresh'), icon: 'pi-refresh', variant: 'secondary', onClick: () => router.reload({ preserveScroll: true }) },
]);

const kpis = computed(() => [
    { label: t('pages.student_reports.kpiTracked'), value: props.stats.total_students ?? '—', icon: 'pi-users', color: 'blue' },
    { label: t('pages.student_reports.kpiAvgScore'), value: props.stats.avg_score !== null ? `${props.stats.avg_score}%` : '—', icon: 'pi-percentage', color: 'red' },
    { label: t('pages.student_reports.kpiActive'), value: props.stats.active_now, icon: 'pi-bolt', color: 'emerald' },
    { label: t('pages.student_reports.kpiCertifications'), value: props.stats.total_certifications, icon: 'pi-verified', color: 'indigo' },
    { label: t('pages.student_reports.kpiAtRisk'), value: props.stats.at_risk_students, icon: 'pi-exclamation-triangle', color: 'amber' },
]);
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.student_reports.headTitle')" />

        <div class="page-bg min-h-screen font-sans">

            <PageHeader
                :title="t('pages.student_reports.title')"
                :subtitle="t('pages.student_reports.subtitle')"
                icon="pi-chart-bar"
                accent="blue"
                :badge="t('pages.student_reports.badge')"
                :actions="headerActions"
            />

            <div class="page-container page-section space-y-8">

                <!-- KPI INSIGHT CARDS -->
                <section v-if="stats" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
                    <div v-for="kpi in kpis" :key="kpi.label"
                        class="bg-white p-3 sm:p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-0">
                        <div class="min-w-0">
                            <p class="text-[10px] sm:text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1 truncate">{{ kpi.label }}</p>
                            <div class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight truncate">{{ kpi.value }}</div>
                        </div>
                        <div :class="`w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-${kpi.color}-50 flex items-center justify-center text-${kpi.color}-600 shrink-0`">
                            <i :class="['pi', kpi.icon, 'text-base sm:text-lg']"></i>
                        </div>
                    </div>
                </section>

                <!-- FILTER CONSOLE -->
                <section class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                    <div class="flex flex-wrap items-center gap-3">
                        <IconField iconPosition="left" class="flex-1 min-w-[220px]">
                            <InputIcon class="pi pi-search text-slate-400" />
                            <InputText v-model="activeFilters.search"
                                :placeholder="t('pages.student_reports.searchPlaceholder')"
                                class="w-full h-11" @keyup.enter="syncData" />
                        </IconField>

                        <Select v-model="activeFilters.course" :options="courses" optionLabel="name" optionValue="id"
                            :placeholder="t('pages.student_reports.allCourses')" class="w-full sm:w-56 h-11" showClear />

                        <Select v-model="activeFilters.performance" :options="performanceOptions"
                            optionLabel="label" optionValue="value" :placeholder="t('pages.student_reports.performance')"
                            class="w-full sm:w-64 h-11" showClear />

                        <MultiSelect v-model="activeFilters.status" :options="availableStatuses"
                            optionLabel="label" optionValue="value" :placeholder="t('pages.student_reports.statuses')"
                            class="w-full sm:w-60 h-11" display="chip" />

                        <div class="flex items-center gap-1 bg-slate-50 p-1 rounded-xl border border-slate-200 ml-auto">
                            <Button icon="pi pi-table" text rounded
                                :severity="displayMode === 'table' ? 'primary' : 'secondary'"
                                @click="displayMode = 'table'" v-tooltip.top="t('pages.student_reports.viewTable')" />
                            <Button icon="pi pi-th-large" text rounded
                                :severity="displayMode === 'grid' ? 'primary' : 'secondary'"
                                @click="displayMode = 'grid'" v-tooltip.top="t('pages.student_reports.viewGrid')" />
                            <div class="w-px h-6 bg-slate-200 mx-1"></div>
                            <Button icon="pi pi-refresh" text rounded severity="secondary" @click="syncData" v-tooltip.top="t('pages.student_reports.refresh')" />
                        </div>
                    </div>
                </section>

                <!-- DATA INTERFACE -->
                <div class="relative min-h-[500px]">
                    <div v-if="isLoading" class="absolute inset-0 bg-white/60 backdrop-blur-sm z-30 flex items-center justify-center rounded-2xl">
                        <div class="flex flex-col items-center gap-3">
                            <i class="pi pi-spin pi-spinner text-4xl text-red-600"></i>
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">{{ t('pages.student_reports.syncing') }}</span>
                        </div>
                    </div>

                    <!-- TABLE VIEW -->
                    <div v-if="displayMode === 'table'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

                        <!-- Bulk Action Bar -->
                        <div v-if="selectedStudents.length" class="bg-slate-900 p-4 flex justify-between items-center">
                            <span class="text-white font-black text-xs px-2">{{ t('pages.student_reports.selectedCount', { count: selectedStudents.length }) }}</span>
                            <div class="flex gap-2">
                                <Button :label="t('pages.student_reports.notify')" icon="pi pi-bell" severity="success" size="small" class="!rounded-lg !text-[11px] !font-bold" />
                                <Button :label="t('pages.student_reports.export')" icon="pi pi-download" severity="secondary" size="small" class="!rounded-lg !text-[11px] !font-bold" />
                            </div>
                        </div>

                        <DataTable :value="students.data" v-model:selection="selectedStudents" dataKey="id"
                            stripedRows responsiveLayout="scroll" class="p-datatable-sm snc-fluid-table">

                            <Column selectionMode="multiple" headerStyle="width: 3.5rem" headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell" />

                            <Column :header="t('pages.student_reports.colStudent')" style="min-width: 0">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-3 py-1 cursor-pointer" @click="openQuickView(data)">
                                        <div class="relative shrink-0">
                                             <Avatar :image="data.avatar" :label="data.name?.charAt(0)" shape="circle" size="large"
                                                 class="!bg-blue-50 !text-blue-600 !font-black border border-blue-100 !w-9 !h-9 sm:!w-12 sm:!h-12 text-xs" />
                                            <span v-if="data.is_online"
                                                class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 border-2 border-white rounded-full"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 leading-tight truncate">{{ data.name }}</p>
                                            <p class="text-[11px] text-slate-400 truncate">{{ data.email }}</p>
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <Column :header="t('pages.student_reports.colPerformance')" style="min-width: 0" headerClass="hidden lg:table-cell" bodyClass="hidden lg:table-cell">
                                <template #body="{ data }">
                                    <div v-if="data.avg_score !== null" class="w-full min-w-[6rem]">
                                        <div class="flex justify-between items-end mb-1.5">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ t('pages.student_reports.avgScore') }}</span>
                                            <span class="text-sm font-black" :class="`text-${getScoreSeverity(data.avg_score)}-600`">{{ data.avg_score }}%</span>
                                        </div>
                                        <ProgressBar :value="data.avg_score" :showValue="false" class="h-1.5 !rounded-full"
                                            :style="`--p-progressbar-value-background: ${getScoreColor(data.avg_score)}`" />
                                    </div>
                                    <span v-else class="text-[11px] italic text-slate-400">{{ t('pages.student_reports.notGraded') }}</span>
                                </template>
                            </Column>

                            <Column field="submissions_count" :header="t('pages.student_reports.colSubmissions')" style="width: 7rem" headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell">
                                <template #body="{ data }">
                                    <span class="text-sm font-bold text-slate-700">{{ data.submissions_count }}</span>
                                </template>
                            </Column>

                            <Column :header="t('pages.student_reports.colLastActivity')" style="min-width: 11rem" headerClass="hidden lg:table-cell" bodyClass="hidden lg:table-cell">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-2">
                                        <i class="pi pi-clock text-slate-300 text-xs"></i>
                                        <div>
                                            <p class="text-xs font-semibold text-slate-700 leading-none">{{ data.last_active_human || t('pages.student_reports.never') }}</p>
                                            <p class="text-[10px] font-mono text-slate-400 mt-0.5">{{ data.ip_address || '—' }}</p>
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <Column :header="t('pages.student_reports.colStatus')" style="width: 8rem">
                                <template #body="{ data }">
                                    <Tag :value="data.is_online ? t('pages.student_reports.online') : t('pages.student_reports.offline')"
                                        :severity="data.is_online ? 'success' : 'secondary'"
                                        class="!text-[10px] !font-bold" />
                                </template>
                            </Column>

                            <Column :header="t('pages.student_reports.actions')" style="min-width: 5rem">
                                <template #body="{ data }">
                                    <div class="hidden md:flex items-center gap-1 justify-end whitespace-nowrap">
                                        <Button icon="pi pi-eye" text rounded severity="secondary" :aria-label="t('pages.student_reports.quickView')" :title="t('pages.student_reports.quickView')" @click="openQuickView(data)" />
                                        <Link :href="route('student-reports.show', data.id)">
                                            <Button icon="pi pi-arrow-right" text rounded :aria-label="t('pages.student_reports.fullFile')" :title="t('pages.student_reports.fullFile')" />
                                        </Link>
                                    </div>
                                    <Button
                                        icon="pi pi-ellipsis-v"
                                        text
                                        rounded
                                        class="md:hidden w-11 h-11 text-slate-500"
                                        :aria-label="t('pages.live_sessions.menu_more')"
                                        :title="t('pages.live_sessions.menu_more')"
                                        @click="toggleRowMenu($event, data)"
                                    />
                                </template>
                            </Column>

                            <template #empty>
                                <div class="text-center py-14 text-slate-500">
                                    <i class="pi pi-search-minus text-3xl mb-2 text-slate-300"></i>
                                    <p class="font-semibold">{{ t('pages.student_reports.emptyTitle') }}</p>
                                    <p class="text-xs text-slate-400 mt-1">{{ t('pages.student_reports.emptyHint') }}</p>
                                </div>
                            </template>
                        </DataTable>

                        <Menu id="student-reports-actions-menu" ref="rowMenu" :model="rowMenuItems" popup :aria-label="t('pages.live_sessions.menu_more')" />

                        <!-- PAGINATOR -->
                        <div v-if="students.total > 0" class="px-5 py-4 bg-slate-50/60 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4">
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                {{ t('pages.student_reports.pagination', { total: students.total, page: students.current_page, last: students.last_page }) }}
                            </p>
                            <Paginator :rows="students.per_page" :totalRecords="students.total"
                                :first="(students.current_page - 1) * students.per_page" @page="onPageChange"
                                template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                                class="!bg-transparent !border-none" />
                        </div>
                    </div>

                    <!-- GRID VIEW -->
                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">
                        <div v-for="student in students.data" :key="student.id"
                            class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                            <div class="p-5">
                                <div class="flex items-center gap-3">
                                    <div class="relative shrink-0">
                                        <Avatar :image="student.avatar" :label="student.name?.charAt(0)" shape="circle" size="large"
                                            class="!bg-blue-50 !text-blue-600 !font-black border border-blue-100" />
                                        <span v-if="student.is_online"
                                            class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 border-2 border-white rounded-full"></span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold text-slate-900 leading-tight truncate">{{ student.name }}</p>
                                        <p class="text-[11px] text-slate-400 truncate">{{ student.email }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3 mt-4">
                                    <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ t('pages.student_reports.score') }}</p>
                                        <p class="font-black text-base" :class="student.avg_score !== null ? `text-${getScoreSeverity(student.avg_score)}-600` : 'text-slate-400'">
                                            {{ student.avg_score !== null ? `${student.avg_score}%` : '—' }}
                                        </p>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ t('pages.student_reports.submissions') }}</p>
                                        <p class="font-black text-base text-slate-900">{{ student.submissions_count || 0 }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 mt-3 text-[11px] text-slate-500">
                                    <i class="pi pi-clock text-slate-400"></i>
                                    <span>{{ student.last_active_human || t('pages.student_reports.neverActive') }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 px-5 py-3 border-t border-slate-100 bg-slate-50/50">
                                <Button icon="pi pi-eye" text rounded severity="secondary" class="flex-1" :aria-label="t('pages.student_reports.quickView')" @click="openQuickView(student)" />
                                <Link :href="route('student-reports.show', student.id)" class="flex-1">
                                    <Button :label="t('pages.student_reports.file')" icon="pi pi-arrow-right" iconPos="right"
                                        class="!w-full !rounded-xl !text-[11px] !font-bold" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QUICK VIEW DIALOG -->
            <SncDialog
                v-model:visible="showQuickView"
                :title="selectedStudent?.name || t('pages.student_reports.profileFallback')"
                :subtitle="selectedStudent?.email || ''"
                icon="pi-id-card"
                icon-class="from-red-500 to-rose-600"
                width="640px"
            >
                <div v-if="selectedStudent" class="space-y-4">

                    <!-- PROFIL & STATUT -->
                    <SncSection icon="pi-user" :title="t('pages.student_reports.secProfile')" :subtitle="t('pages.student_reports.secProfileSub')"
                        icon-class="from-blue-500 to-blue-600">
                        <div class="flex items-center gap-4">
                            <div class="relative shrink-0">
                                <Avatar :image="selectedStudent.avatar" :label="selectedStudent.name?.charAt(0)"
                                    shape="circle" size="xlarge"
                                    class="!bg-blue-50 !text-blue-600 !font-black border-2 border-blue-100" />
                                <span v-if="selectedStudent.is_online"
                                    class="absolute bottom-1 right-1 w-4 h-4 bg-emerald-500 border-2 border-white rounded-full"></span>
                            </div>
                            <div class="min-w-0 flex-1 space-y-2">
                                <div class="flex flex-wrap gap-2">
                                    <Tag :value="selectedStudent.is_online ? t('pages.student_reports.online') : t('pages.student_reports.offline')"
                                        :severity="selectedStudent.is_online ? 'success' : 'secondary'" class="!text-[10px] !font-bold" />
                                    <Tag :value="`ID #${selectedStudent.id}`" severity="info" class="!text-[10px] !font-bold" />
                                    <Tag v-if="selectedStudent.avg_score !== null"
                                        :value="t('pages.student_reports.scoreTag', { score: selectedStudent.avg_score })"
                                        :severity="getScoreSeverity(selectedStudent.avg_score)" class="!text-[10px] !font-bold" />
                                </div>
                                <div class="grid grid-cols-2 gap-3 text-xs">
                                    <div class="rounded-lg bg-slate-50 border border-slate-100 px-3 py-2">
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ t('pages.student_reports.colLastActivity') }}</p>
                                        <p class="font-semibold text-slate-700 mt-0.5">{{ selectedStudent.last_active_human || t('pages.student_reports.never') }}</p>
                                    </div>
                                    <div class="rounded-lg bg-slate-50 border border-slate-100 px-3 py-2">
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ t('pages.student_reports.ipAddress') }}</p>
                                        <p class="font-mono font-semibold text-slate-700 mt-0.5">{{ selectedStudent.ip_address || '—' }}</p>
                                    </div>
                                    <div class="rounded-lg bg-slate-50 border border-slate-100 px-3 py-2">
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ t('pages.student_reports.submissions') }}</p>
                                        <p class="font-semibold text-slate-700 mt-0.5">{{ selectedStudent.submissions_count }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </SncSection>

                    <!-- COMPÉTENCES -->
                    <SncSection icon="pi-chart-bar" :title="t('pages.student_reports.secSkills')" :subtitle="t('pages.student_reports.secSkillsSub')"
                        icon-class="from-emerald-500 to-teal-600">
                        <div v-if="selectedStudent.skills?.length" class="space-y-4">
                            <div v-for="s in selectedStudent.skills" :key="s.name">
                                <div class="flex justify-between items-center mb-1.5">
                                    <span class="text-xs font-bold text-slate-700">{{ s.name }}</span>
                                    <span class="text-xs font-black" :class="`text-${getScoreSeverity(s.score)}-600`">{{ s.score }}%</span>
                                </div>
                                <ProgressBar :value="s.score" :showValue="false" class="h-2 !rounded-full"
                                    :style="`--p-progressbar-value-background: ${getScoreColor(s.score)}`" />
                            </div>
                        </div>
                        <p v-else class="text-xs text-slate-400 italic text-center py-2">{{ t('pages.student_reports.noSkills') }}</p>
                    </SncSection>

                    <!-- ACTIVITÉ RÉCENTE -->
                    <SncSection icon="pi-history" :title="t('pages.student_reports.secActivity')" :subtitle="t('pages.student_reports.secActivitySub')"
                        icon-class="from-amber-500 to-orange-600">
                        <div v-if="selectedStudent.recent_activities?.length" class="space-y-2.5">
                            <div v-for="(a, i) in selectedStudent.recent_activities" :key="i"
                                class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50/60 px-3.5 py-2.5">
                                <i :class="a.icon" class="text-sm mt-0.5 shrink-0"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold text-slate-700 leading-snug">{{ a.description }}</p>
                                    <p class="text-[10px] font-bold text-slate-400 mt-0.5">{{ a.date_human }}</p>
                                </div>
                                <span class="text-[10px] font-mono font-bold text-slate-500 shrink-0">{{ a.score }}</span>
                            </div>
                        </div>
                        <p v-else class="text-xs text-slate-400 italic text-center py-2">{{ t('pages.student_reports.noActivity') }}</p>
                    </SncSection>

                </div>

                <template #footer>
                    <Button :label="t('pages.student_reports.close')" text severity="secondary" @click="showQuickView = false" />
                    <Link v-if="selectedStudent" :href="route('student-reports.show', selectedStudent.id)">
                        <Button :label="t('pages.student_reports.fullFile')" icon="pi pi-external-link" iconPos="right"
                            class="!font-bold" />
                    </Link>
                </template>
            </SncDialog>

        </div>
    </AppLayout>
</template>
