import{N as L,a3 as F,o as u,g as h,a0 as x,a1 as f,aK as y,aL as J,a8 as E,aq as X,ao as w,ac as Y,ai as q,i as z,c as _,aj as O,h as R,f as i,au as tt,av as et,aa as k,b as d,n as C,bU as at,u as st,a as nt,B as ot,d as l,e as s,Z as it,t as b,s as D,v as I,l as $,A as j,m as H,p as T}from"./app-L72KCiNk.js";import{b as rt,_ as lt}from"./AppLayout-Bz7MaBw4.js";import{_ as ct}from"./PageHeader-DNwxHXOD.js";import{s as dt}from"./index-BsoDdiC2.js";import{s as U}from"./index-DVW_ClpH.js";import{s as ut,a as B}from"./index-CKmRjCzf.js";import{s as W}from"./index-BvsM3xeA.js";import{s as bt}from"./index-Cd3tXwiG.js";import{s as S}from"./index-BDVPDHgs.js";import{s as pt}from"./index-DfveCGap.js";import{R as M}from"./index-Dfswpds_.js";import{s as K}from"./index-EcxpeCQC.js";import{s as ht}from"./index-Ga_PqtYb.js";import{s as ft}from"./index-JPA8bclR.js";import{s as vt}from"./index-CnK5EX6L.js";import{s as gt,a as mt}from"./index-DQoaSSlp.js";import{_ as xt}from"./_plugin-vue_export-helper-DlAUqK2U.js";import"./AppLayout.vue_vue_type_style_index_0_lang-iZx17L-P.js";import"./index-BD9Urb3s.js";import"./index-DhgEdQz5.js";import"./index-BmOZDz3o.js";import"./index-D_oXf56_.js";import"./index-CpDbSrXx.js";import"./index-7csp0Sc7.js";import"./index-CGuInDoW.js";import"./index-BRpVS3zS.js";import"./index-C4w2Gr1w.js";import"./SncConfirmDialog-BYrS603V.js";import"./index-Dr3BJt39.js";import"./index-CqXIjQxV.js";import"./index-4RXgdGOK.js";import"./index-B3XnMzJT.js";import"./index-CbeH-h8D.js";import"./index-CLExmpne.js";import"./index-MjTLfLcU.js";var _t=function(t){var a=t.dt;return`
.p-tabs {
    display: flex;
    flex-direction: column;
}

.p-tablist {
    display: flex;
    position: relative;
}

.p-tabs-scrollable > .p-tablist {
    overflow: hidden;
}

.p-tablist-viewport {
    overflow-x: auto;
    overflow-y: hidden;
    scroll-behavior: smooth;
    scrollbar-width: none;
    overscroll-behavior: contain auto;
}

.p-tablist-viewport::-webkit-scrollbar {
    display: none;
}

.p-tablist-tab-list {
    position: relative;
    display: flex;
    background: `.concat(a("tabs.tablist.background"),`;
    border-style: solid;
    border-color: `).concat(a("tabs.tablist.border.color"),`;
    border-width: `).concat(a("tabs.tablist.border.width"),`;
}

.p-tablist-content {
    flex-grow: 1;
}

.p-tablist-nav-button {
    all: unset;
    position: absolute !important;
    flex-shrink: 0;
    top: 0;
    z-index: 2;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: `).concat(a("tabs.nav.button.background"),`;
    color: `).concat(a("tabs.nav.button.color"),`;
    width: `).concat(a("tabs.nav.button.width"),`;
    transition: color `).concat(a("tabs.transition.duration"),", outline-color ").concat(a("tabs.transition.duration"),", box-shadow ").concat(a("tabs.transition.duration"),`;
    box-shadow: `).concat(a("tabs.nav.button.shadow"),`;
    outline-color: transparent;
    cursor: pointer;
}

.p-tablist-nav-button:focus-visible {
    z-index: 1;
    box-shadow: `).concat(a("tabs.nav.button.focus.ring.shadow"),`;
    outline: `).concat(a("tabs.nav.button.focus.ring.width")," ").concat(a("tabs.nav.button.focus.ring.style")," ").concat(a("tabs.nav.button.focus.ring.color"),`;
    outline-offset: `).concat(a("tabs.nav.button.focus.ring.offset"),`;
}

.p-tablist-nav-button:hover {
    color: `).concat(a("tabs.nav.button.hover.color"),`;
}

.p-tablist-prev-button {
    left: 0;
}

.p-tablist-next-button {
    right: 0;
}

.p-tab {
    flex-shrink: 0;
    cursor: pointer;
    user-select: none;
    position: relative;
    border-style: solid;
    white-space: nowrap;
    background: `).concat(a("tabs.tab.background"),`;
    border-width: `).concat(a("tabs.tab.border.width"),`;
    border-color: `).concat(a("tabs.tab.border.color"),`;
    color: `).concat(a("tabs.tab.color"),`;
    padding: `).concat(a("tabs.tab.padding"),`;
    font-weight: `).concat(a("tabs.tab.font.weight"),`;
    transition: background `).concat(a("tabs.transition.duration"),", border-color ").concat(a("tabs.transition.duration"),", color ").concat(a("tabs.transition.duration"),", outline-color ").concat(a("tabs.transition.duration"),", box-shadow ").concat(a("tabs.transition.duration"),`;
    margin: `).concat(a("tabs.tab.margin"),`;
    outline-color: transparent;
}

.p-tab:not(.p-disabled):focus-visible {
    z-index: 1;
    box-shadow: `).concat(a("tabs.tab.focus.ring.shadow"),`;
    outline: `).concat(a("tabs.tab.focus.ring.width")," ").concat(a("tabs.tab.focus.ring.style")," ").concat(a("tabs.tab.focus.ring.color"),`;
    outline-offset: `).concat(a("tabs.tab.focus.ring.offset"),`;
}

.p-tab:not(.p-tab-active):not(.p-disabled):hover {
    background: `).concat(a("tabs.tab.hover.background"),`;
    border-color: `).concat(a("tabs.tab.hover.border.color"),`;
    color: `).concat(a("tabs.tab.hover.color"),`;
}

.p-tab-active {
    background: `).concat(a("tabs.tab.active.background"),`;
    border-color: `).concat(a("tabs.tab.active.border.color"),`;
    color: `).concat(a("tabs.tab.active.color"),`;
}

.p-tabpanels {
    background: `).concat(a("tabs.tabpanel.background"),`;
    color: `).concat(a("tabs.tabpanel.color"),`;
    padding: `).concat(a("tabs.tabpanel.padding"),`;
    outline: 0 none;
}

.p-tabpanel:focus-visible {
    box-shadow: `).concat(a("tabs.tabpanel.focus.ring.shadow"),`;
    outline: `).concat(a("tabs.tabpanel.focus.ring.width")," ").concat(a("tabs.tabpanel.focus.ring.style")," ").concat(a("tabs.tabpanel.focus.ring.color"),`;
    outline-offset: `).concat(a("tabs.tabpanel.focus.ring.offset"),`;
}

.p-tablist-active-bar {
    z-index: 1;
    display: block;
    position: absolute;
    bottom: `).concat(a("tabs.active.bar.bottom"),`;
    height: `).concat(a("tabs.active.bar.height"),`;
    background: `).concat(a("tabs.active.bar.background"),`;
    transition: 250ms cubic-bezier(0.35, 0, 0.25, 1);
}
`)},yt={root:function(t){var a=t.props;return["p-tabs p-component",{"p-tabs-scrollable":a.scrollable}]}},wt=L.extend({name:"tabs",theme:_t,classes:yt}),kt={name:"BaseTabs",extends:S,props:{value:{type:String,default:void 0},lazy:{type:Boolean,default:!1},scrollable:{type:Boolean,default:!1},showNavigators:{type:Boolean,default:!0},tabindex:{type:Number,default:0},selectOnFocus:{type:Boolean,default:!1}},style:wt,provide:function(){return{$pcTabs:this,$parentInstance:this}}},G={name:"Tabs",extends:kt,inheritAttrs:!1,emits:["update:value"],data:function(){return{id:this.$attrs.id,d_value:this.value}},watch:{"$attrs.id":function(t){this.id=t||F()},value:function(t){this.d_value=t}},mounted:function(){this.id=this.id||F()},methods:{updateValue:function(t){this.d_value!==t&&(this.d_value=t,this.$emit("update:value",t))},isVertical:function(){return this.orientation==="vertical"}}};function $t(e,t,a,n,c,o){return u(),h("div",f({class:e.cx("root")},e.ptmi("root")),[x(e.$slots,"default")],16)}G.render=$t;var Tt={root:"p-tablist",content:function(t){var a=t.instance;return["p-tablist-content",{"p-tablist-viewport":a.$pcTabs.scrollable}]},tabList:"p-tablist-tab-list",activeBar:"p-tablist-active-bar",prevButton:"p-tablist-prev-button p-tablist-nav-button",nextButton:"p-tablist-next-button p-tablist-nav-button"},Bt=L.extend({name:"tablist",classes:Tt}),Ct={name:"BaseTabList",extends:S,props:{},style:Bt,provide:function(){return{$pcTabList:this,$parentInstance:this}}},Q={name:"TabList",extends:Ct,inheritAttrs:!1,inject:["$pcTabs"],data:function(){return{isPrevButtonEnabled:!1,isNextButtonEnabled:!0}},resizeObserver:void 0,watch:{showNavigators:function(t){t?this.bindResizeObserver():this.unbindResizeObserver()},activeValue:{flush:"post",handler:function(){this.updateInkBar()}}},mounted:function(){var t=this;this.$nextTick(function(){t.updateInkBar()}),this.showNavigators&&(this.updateButtonState(),this.bindResizeObserver())},updated:function(){this.showNavigators&&this.updateButtonState()},beforeUnmount:function(){this.unbindResizeObserver()},methods:{onScroll:function(t){this.showNavigators&&this.updateButtonState(),t.preventDefault()},onPrevButtonClick:function(){var t=this.$refs.content,a=y(t),n=t.scrollLeft-a;t.scrollLeft=n<=0?0:n},onNextButtonClick:function(){var t=this.$refs.content,a=y(t)-this.getVisibleButtonWidths(),n=t.scrollLeft+a,c=t.scrollWidth-a;t.scrollLeft=n>=c?c:n},bindResizeObserver:function(){var t=this;this.resizeObserver=new ResizeObserver(function(){return t.updateButtonState()}),this.resizeObserver.observe(this.$refs.list)},unbindResizeObserver:function(){var t;(t=this.resizeObserver)===null||t===void 0||t.unobserve(this.$refs.list),this.resizeObserver=void 0},updateInkBar:function(){var t=this.$refs,a=t.content,n=t.inkbar,c=t.tabs,o=E(a,'[data-pc-name="tab"][data-p-active="true"]');this.$pcTabs.isVertical()?(n.style.height=X(o)+"px",n.style.top=w(o).top-w(c).top+"px"):(n.style.width=Y(o)+"px",n.style.left=w(o).left-w(c).left+"px")},updateButtonState:function(){var t=this.$refs,a=t.list,n=t.content,c=n.scrollLeft,o=n.scrollTop,m=n.scrollWidth,N=n.scrollHeight,P=n.offsetWidth,V=n.offsetHeight,v=[y(n),J(n)],p=v[0],r=v[1];this.$pcTabs.isVertical()?(this.isPrevButtonEnabled=o!==0,this.isNextButtonEnabled=a.offsetHeight>=V&&parseInt(o)!==N-r):(this.isPrevButtonEnabled=c!==0,this.isNextButtonEnabled=a.offsetWidth>=P&&parseInt(c)!==m-p)},getVisibleButtonWidths:function(){var t=this.$refs,a=t.prevBtn,n=t.nextBtn;return[a,n].reduce(function(c,o){return o?c+y(o):c},0)}},computed:{templates:function(){return this.$pcTabs.$slots},activeValue:function(){return this.$pcTabs.d_value},showNavigators:function(){return this.$pcTabs.scrollable&&this.$pcTabs.showNavigators},prevButtonAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.previous:void 0},nextButtonAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.next:void 0}},components:{ChevronLeftIcon:rt,ChevronRightIcon:pt},directives:{ripple:M}},At=["aria-label","tabindex"],Lt=["aria-orientation"],St=["aria-label","tabindex"];function Nt(e,t,a,n,c,o){var m=q("ripple");return u(),h("div",f({ref:"list",class:e.cx("root")},e.ptmi("root")),[o.showNavigators&&c.isPrevButtonEnabled?z((u(),h("button",f({key:0,ref:"prevButton",class:e.cx("prevButton"),"aria-label":o.prevButtonAriaLabel,tabindex:o.$pcTabs.tabindex,onClick:t[0]||(t[0]=function(){return o.onPrevButtonClick&&o.onPrevButtonClick.apply(o,arguments)})},e.ptm("prevButton"),{"data-pc-group-section":"navigator"}),[(u(),_(O(o.templates.previcon||"ChevronLeftIcon"),f({"aria-hidden":"true"},e.ptm("prevIcon")),null,16))],16,At)),[[m]]):R("",!0),i("div",f({ref:"content",class:e.cx("content"),onScroll:t[1]||(t[1]=function(){return o.onScroll&&o.onScroll.apply(o,arguments)})},e.ptm("content")),[i("div",f({ref:"tabs",class:e.cx("tabList"),role:"tablist","aria-orientation":o.$pcTabs.orientation||"horizontal"},e.ptm("tabList")),[x(e.$slots,"default"),i("span",f({ref:"inkbar",class:e.cx("activeBar"),role:"presentation","aria-hidden":"true"},e.ptm("activeBar")),null,16)],16,Lt)],16),o.showNavigators&&c.isNextButtonEnabled?z((u(),h("button",f({key:1,ref:"nextButton",class:e.cx("nextButton"),"aria-label":o.nextButtonAriaLabel,tabindex:o.$pcTabs.tabindex,onClick:t[2]||(t[2]=function(){return o.onNextButtonClick&&o.onNextButtonClick.apply(o,arguments)})},e.ptm("nextButton"),{"data-pc-group-section":"navigator"}),[(u(),_(O(o.templates.nexticon||"ChevronRightIcon"),f({"aria-hidden":"true"},e.ptm("nextIcon")),null,16))],16,St)),[[m]]):R("",!0)],16)}Q.render=Nt;var Pt={root:function(t){var a=t.instance,n=t.props;return["p-tab",{"p-tab-active":a.active,"p-disabled":n.disabled}]}},Vt=L.extend({name:"tab",classes:Pt}),Dt={name:"BaseTab",extends:S,props:{value:{type:String,default:void 0},disabled:{type:Boolean,default:!1},as:{type:String,default:"BUTTON"},asChild:{type:Boolean,default:!1}},style:Vt,provide:function(){return{$pcTab:this,$parentInstance:this}}},A={name:"Tab",extends:Dt,inheritAttrs:!1,inject:["$pcTabs","$pcTabList"],methods:{onFocus:function(){this.$pcTabs.selectOnFocus&&this.changeActiveValue()},onClick:function(){this.changeActiveValue()},onKeydown:function(t){switch(t.code){case"ArrowRight":this.onArrowRightKey(t);break;case"ArrowLeft":this.onArrowLeftKey(t);break;case"Home":this.onHomeKey(t);break;case"End":this.onEndKey(t);break;case"PageDown":this.onPageDownKey(t);break;case"PageUp":this.onPageUpKey(t);break;case"Enter":case"NumpadEnter":case"Space":this.onEnterKey(t);break}},onArrowRightKey:function(t){var a=this.findNextTab(t.currentTarget);a?this.changeFocusedTab(t,a):this.onHomeKey(t),t.preventDefault()},onArrowLeftKey:function(t){var a=this.findPrevTab(t.currentTarget);a?this.changeFocusedTab(t,a):this.onEndKey(t),t.preventDefault()},onHomeKey:function(t){var a=this.findFirstTab();this.changeFocusedTab(t,a),t.preventDefault()},onEndKey:function(t){var a=this.findLastTab();this.changeFocusedTab(t,a),t.preventDefault()},onPageDownKey:function(t){this.scrollInView(this.findLastTab()),t.preventDefault()},onPageUpKey:function(t){this.scrollInView(this.findFirstTab()),t.preventDefault()},onEnterKey:function(t){this.changeActiveValue(),t.preventDefault()},findNextTab:function(t){var a=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1,n=a?t:t.nextElementSibling;return n?k(n,"data-p-disabled")||k(n,"data-pc-section")==="inkbar"?this.findNextTab(n):E(n,'[data-pc-name="tab"]'):null},findPrevTab:function(t){var a=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1,n=a?t:t.previousElementSibling;return n?k(n,"data-p-disabled")||k(n,"data-pc-section")==="inkbar"?this.findPrevTab(n):E(n,'[data-pc-name="tab"]'):null},findFirstTab:function(){return this.findNextTab(this.$pcTabList.$refs.content.firstElementChild,!0)},findLastTab:function(){return this.findPrevTab(this.$pcTabList.$refs.content.lastElementChild,!0)},changeActiveValue:function(){this.$pcTabs.updateValue(this.value)},changeFocusedTab:function(t,a){et(a),this.scrollInView(a)},scrollInView:function(t){var a;t==null||(a=t.scrollIntoView)===null||a===void 0||a.call(t,{block:"nearest"})}},computed:{active:function(){var t;return tt((t=this.$pcTabs)===null||t===void 0?void 0:t.d_value,this.value)},id:function(){var t;return"".concat((t=this.$pcTabs)===null||t===void 0?void 0:t.id,"_tab_").concat(this.value)},ariaControls:function(){var t;return"".concat((t=this.$pcTabs)===null||t===void 0?void 0:t.id,"_tabpanel_").concat(this.value)},attrs:function(){return f(this.asAttrs,this.a11yAttrs,this.ptmi("root",this.ptParams))},asAttrs:function(){return this.as==="BUTTON"?{type:"button",disabled:this.disabled}:void 0},a11yAttrs:function(){return{id:this.id,tabindex:this.active?this.$pcTabs.tabindex:-1,role:"tab","aria-selected":this.active,"aria-controls":this.ariaControls,"data-pc-name":"tab","data-p-disabled":this.disabled,"data-p-active":this.active,onFocus:this.onFocus,onKeydown:this.onKeydown}},ptParams:function(){return{context:{active:this.active}}}},directives:{ripple:M}};function It(e,t,a,n,c,o){var m=q("ripple");return e.asChild?x(e.$slots,"default",{key:1,class:C(e.cx("root")),active:o.active,a11yAttrs:o.a11yAttrs,onClick:o.onClick}):z((u(),_(O(e.as),f({key:0,class:e.cx("root"),onClick:o.onClick},o.attrs),{default:d(function(){return[x(e.$slots,"default")]}),_:3},16,["class","onClick"])),[[m]])}A.render=It;var Kt={root:"p-tabpanels"},Et=L.extend({name:"tabpanels",classes:Kt}),zt={name:"BaseTabPanels",extends:S,props:{},style:Et,provide:function(){return{$pcTabPanels:this,$parentInstance:this}}},Z={name:"TabPanels",extends:zt,inheritAttrs:!1};function Ot(e,t,a,n,c,o){return u(),h("div",f({class:e.cx("root"),role:"presentation"},e.ptmi("root")),[x(e.$slots,"default")],16)}Z.render=Ot;const Rt={class:"page-bg min-h-screen font-sans"},Ft={class:"flex items-center gap-3 mt-3"},jt={class:"text-xs font-bold text-slate-400 uppercase tracking-widest"},Ht={class:"page-container page-section space-y-8"},Ut={key:0,class:"grid grid-cols-1 md:grid-cols-4 gap-6"},Wt={key:1,class:"grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6"},qt={class:"flex justify-between items-start mb-4"},Mt={class:"text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1"},Gt={class:"text-3xl font-black text-slate-900 tracking-tighter italic"},Qt={class:"bg-white rounded-[1rem] border border-slate-200 shadow-2xl overflow-hidden min-h-[600px]"},Zt={class:"grid grid-cols-1 lg:grid-cols-3 gap-10"},Jt={class:"lg:col-span-2 space-y-6"},Xt={class:"text-sm font-black text-slate-800 uppercase tracking-widest flex items-center gap-2"},Yt={class:"h-[400px] p-6 rounded-[1rem] bg-slate-50 border border-slate-100 shadow-inner"},te={class:"space-y-6"},ee={class:"text-sm font-black text-slate-800 uppercase tracking-widest text-center"},ae={class:"h-[400px] flex items-center justify-center p-6 rounded-[1rem] border border-red-50"},se={class:"space-y-8"},ne={class:"flex justify-between items-center mb-6"},oe={class:"bg-[#0F172A] px-8 py-5 flex justify-between items-center"},ie={class:"font-black text-white text-sm tracking-tight italic uppercase"},re={class:"text-[10px] font-black text-red-500 uppercase tracking-[0.2em]"},le={class:"font-black text-slate-800 tracking-tight italic"},ce={key:0,class:"w-32"},de={class:"flex justify-between mb-1"},ue={class:"text-[10px] font-bold text-slate-400"},be={class:"text-xs font-black text-slate-800"},pe={key:1,class:"text-[10px] italic text-slate-300"},he={class:"text-xs font-bold text-slate-500 italic"},fe={class:"max-w-3xl mx-auto py-10 px-6 bg-slate-50 rounded-[1rem] border border-slate-100"},ve={class:"p-6 bg-white rounded-xl shadow-sm border border-slate-200 mb-6 group hover:shadow-lg transition-all"},ge={class:"text-sm font-black text-slate-800 italic uppercase mb-1"},me={class:"text-xs text-slate-500 font-medium leading-relaxed mb-3"},xe={class:"text-[10px] font-black text-slate-400 tracking-widest italic"},_e=at({__name:"StudentReport",props:{student:{},globalStats:{},coursesData:{},performanceOverTime:{},skillsData:{},activityLog:{}},setup(e){const{t}=st(),a=e;nt();const n=H(!0),c=H("");ot(()=>{setTimeout(()=>{n.value=!1},600)});const o=T(()=>{if(!c.value)return a.coursesData;const v=c.value.toLowerCase();return a.coursesData.map(p=>({...p,exams:p.exams.filter(r=>r.title.toLowerCase().includes(v))})).filter(p=>p.exams.length>0||p.title.toLowerCase().includes(v))}),m=T(()=>({labels:a.performanceOverTime.labels,datasets:[{label:t("pages.student_report_detail.chartPerformance"),data:a.performanceOverTime.scores,fill:!0,borderColor:"#E11D48",backgroundColor:"rgba(225, 29, 72, 0.1)",tension:.4,pointBackgroundColor:"#fff",pointBorderColor:"#E11D48",pointRadius:5}]})),N=T(()=>{var v,p;return{labels:((v=a.skillsData)==null?void 0:v.labels)||[],datasets:[{label:t("pages.student_report_detail.chartMastery"),data:((p=a.skillsData)==null?void 0:p.scores)||[],backgroundColor:"rgba(225, 29, 72, 0.2)",borderColor:"#E11D48",pointBackgroundColor:"#E11D48"}]}}),P=v=>({Réussi:{sev:"success",icon:"pi-check-circle"},Échoué:{sev:"danger",icon:"pi-times"},"En attente":{sev:"warn",icon:"pi-clock"}})[v]||{sev:"secondary",icon:"pi-info"},V=T(()=>[{label:t("pages.student_report_detail.backToReports"),icon:"pi-arrow-left",variant:"secondary",href:route("student-reports.index")}]);return(v,p)=>(u(),_(lt,null,{default:d(()=>[l(s(it),{title:s(t)("pages.student_report_detail.headTitle",{name:e.student.name})},null,8,["title"]),i("div",Rt,[l(ct,{title:e.student.name,subtitle:s(t)("pages.student_report_detail.subtitle",{email:e.student.email}),icon:"pi-id-card",accent:"blue",badge:s(t)("pages.student_report_detail.badge"),actions:V.value},{meta:d(()=>[i("div",Ft,[l(s(dt),{image:e.student.avatar,label:e.student.name.charAt(0),shape:"circle",size:"small",class:"!bg-white/10 !text-white !font-black border border-white/20"},null,8,["image","label"]),i("span",jt,b(s(t)("pages.student_report_detail.studentId",{id:e.student.id})),1)])]),_:1},8,["title","subtitle","badge","actions"]),i("main",Ht,[n.value?(u(),h("div",Ut,[(u(),h(D,null,I(4,r=>l(s(vt),{height:"120px",borderRadius:"32px",key:r})),64))])):(u(),h("section",Wt,[(u(!0),h(D,null,I([{l:s(t)("pages.student_report_detail.kpiAvg"),v:e.globalStats.average_score+"%",t:e.globalStats.score_trend,i:"pi-percentage",c:"text-red-600",bg:"bg-red-50"},{l:s(t)("pages.student_report_detail.kpiSuccess"),v:e.globalStats.success_rate+"%",t:e.globalStats.success_trend,i:"pi-verified",c:"text-emerald-600",bg:"bg-emerald-50"},{l:s(t)("pages.student_report_detail.kpiCompleted"),v:e.globalStats.completed_exams,t:e.globalStats.success_trend,i:"pi-book",c:"text-blue-600",bg:"bg-blue-50"},{l:s(t)("pages.student_report_detail.kpiAttempts"),v:e.globalStats.total_attempts,t:e.globalStats.success_trend,i:"pi-sync",c:"text-slate-900",bg:"bg-slate-100"}],r=>(u(),h("div",{key:r.l,class:"bg-white p-6 rounded-[1rem] border border-slate-200/50 shadow-sm hover:shadow-xl transition-all group"},[i("div",qt,[i("div",{class:C([r.bg,"w-10 h-10 rounded-xl flex items-center justify-center group-hover:rotate-6 transition-transform"])},[i("i",{class:C(["pi",r.i,r.c,"text-lg"])},null,2)],2),r.t!==void 0&&r.t!==null?(u(),_(s(W),{key:0,severity:Number(r.t)>=0?"success":"danger",value:`${Number(r.t)>0?"+":""}${r.t}%`,class:"!text-[9px] !font-black"},null,8,["severity","value"])):R("",!0)]),i("p",Mt,b(r.l),1),i("h2",Gt,b(r.v),1)]))),128))])),i("div",Qt,[l(s(G),{value:"0"},{default:d(()=>[l(s(Q),{class:"bg-slate-50/50 px-8 pt-4 !border-none"},{default:d(()=>[l(s(A),{value:"0",class:"!text-xs !font-black !uppercase !tracking-widest !px-8"},{default:d(()=>[$(b(s(t)("pages.student_report_detail.tabPerformance")),1)]),_:1}),l(s(A),{value:"1",class:"!text-xs !font-black !uppercase !tracking-widest !px-8"},{default:d(()=>[$(b(s(t)("pages.student_report_detail.tabGrades")),1)]),_:1}),l(s(A),{value:"2",class:"!text-xs !font-black !uppercase !tracking-widest !px-8"},{default:d(()=>[$(b(s(t)("pages.student_report_detail.tabTimeline")),1)]),_:1})]),_:1}),l(s(Z),{class:"p-8"},{default:d(()=>[l(s(K),{value:"0"},{default:d(()=>[i("div",Zt,[i("div",Jt,[i("h3",Xt,[p[1]||(p[1]=i("span",{class:"w-2 h-2 bg-red-600 rounded-full animate-pulse"},null,-1)),$(" "+b(s(t)("pages.student_report_detail.evolutionTitle")),1)]),i("div",Yt,[l(s(U),{type:"line",data:m.value,class:"h-full",options:{maintainAspectRatio:!1,plugins:{legend:{display:!1}}}},null,8,["data"])])]),i("div",te,[i("h3",ee,b(s(t)("pages.student_report_detail.skillMapping")),1),i("div",ae,[l(s(U),{type:"radar",data:N.value,class:"h-full",options:{maintainAspectRatio:!1,plugins:{legend:{display:!1}}}},null,8,["data"])])])])]),_:1}),l(s(K),{value:"1"},{default:d(()=>[i("div",se,[i("div",ne,[l(s(gt),{iconPosition:"left",class:"w-full max-w-md"},{default:d(()=>[l(s(mt),{class:"pi pi-search"}),l(s(ht),{modelValue:c.value,"onUpdate:modelValue":p[0]||(p[0]=r=>c.value=r),placeholder:s(t)("pages.student_report_detail.searchPlaceholder"),class:"w-full !rounded-xl !bg-slate-50 border-none !py-4"},null,8,["modelValue","placeholder"])]),_:1})]),(u(!0),h(D,null,I(o.value,r=>(u(),h("div",{key:r.id,class:"rounded-[1rem] border border-slate-100 overflow-hidden mb-8 shadow-sm"},[i("div",oe,[i("h4",ie,b(r.title),1),i("span",re,b(s(t)("pages.student_report_detail.assessmentsCount",{count:r.exams.length})),1)]),l(s(ut),{value:r.exams,responsiveLayout:"scroll",class:"p-datatable-v40"},{default:d(()=>[l(s(B),{field:"title",header:s(t)("pages.student_report_detail.colAssessment")},{body:d(({data:g})=>[i("span",le,b(g.title),1)]),_:1},8,["header"]),l(s(B),{header:s(t)("pages.student_report_detail.colScore")},{body:d(({data:g})=>[g.score!==null?(u(),h("div",ce,[i("div",de,[i("span",ue,b(s(t)("pages.student_report_detail.precision")),1),i("span",be,b(g.score)+"%",1)]),l(s(bt),{value:g.score,showValue:!1,class:"h-1 bg-slate-100",style:j(`--p-progressbar-value-background: ${g.score>=50?"#10B981":"#E11D48"}`)},null,8,["value","style"])])):(u(),h("span",pe,b(s(t)("pages.student_report_detail.pending")),1))]),_:1},8,["header"]),l(s(B),{header:s(t)("pages.student_report_detail.colStatus")},{body:d(({data:g})=>[l(s(W),{severity:P(g.status).sev,value:g.status,class:"!text-[9px] !font-black !uppercase !px-3 !rounded-lg"},null,8,["severity","value"])]),_:1},8,["header"]),l(s(B),{header:s(t)("pages.student_report_detail.colDate")},{body:d(({data:g})=>[i("span",he,b(g.attempted_at||"---"),1)]),_:1},8,["header"])]),_:1},8,["value"])]))),128))])]),_:1}),l(s(K),{value:"2"},{default:d(()=>[i("div",fe,[l(s(ft),{value:e.activityLog,align:"alternate",class:"custom-timeline-v40"},{marker:d(r=>[i("div",{class:"w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-xl",style:j({backgroundColor:r.item.color})},[i("i",{class:C([r.item.icon,"text-sm"])},null,2)],4)]),content:d(r=>[i("div",ve,[i("h4",ge,b(r.item.status),1),i("p",me,b(r.item.description),1),i("span",xe,b(r.item.date),1)])]),_:1},8,["value"])])]),_:1})]),_:1})]),_:1})])])])]),_:1}))}}),aa=xt(_e,[["__scopeId","data-v-833e0991"]]);export{aa as default};
