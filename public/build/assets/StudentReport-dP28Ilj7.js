import{J as S,a4 as I,o as b,c as p,L as x,M as v,an as y,ao as G,a9 as E,aK as X,aL as k,ad as Y,aj as U,l as K,f as w,ak as R,g as z,b as s,ar as tt,as as et,ab as $,w as u,n as _,bS as at,x as st,y as nt,a as c,u as l,Z as ot,t as h,F as P,e as N,j as T,s as F,r as O,p as V}from"./app-CmOH-1_J.js";import{a as it,_ as rt}from"./AppLayout-X_3DTF5x.js";import{s as lt}from"./index-DUwPG9wH.js";import{s as j}from"./index-C81CKXPr.js";import{s as ct,a as B}from"./index-DHvmTGFx.js";import{s as H}from"./index-BNLR77rM.js";import{s as dt}from"./index-Brr6i-aZ.js";import{s as A}from"./index-iyK7KXSc.js";import{s as ut}from"./index-D9ABT2bB.js";import{R as M}from"./index-DfX9Bp0U.js";import{s as D}from"./index-Ah1TOXlB.js";import{s as W}from"./index-BcKK1Nx-.js";import{s as bt}from"./index---9XPg96.js";import{s as pt}from"./index-BrShsmuK.js";import{s as ft}from"./index-CrxYTB6R.js";import{a as ht,b as vt}from"./index-DF3QjxUT.js";import{_ as mt}from"./_plugin-vue_export-helper-DlAUqK2U.js";import"./AppLayout.vue_vue_type_style_index_0_lang-BKmBAH1O.js";import"./index-BGe5UWjj.js";import"./index-m1SF9dQw.js";import"./index-dB2KKPKC.js";import"./index-CZZoEyaa.js";import"./index-C5K_594j.js";import"./index-B3NJSK-O.js";import"./index-CFiekzS5.js";import"./index-VIRnqVG6.js";import"./index-Cn22zLma.js";import"./index-C2jE1i0L.js";import"./index-m0QmBD9L.js";import"./index-2marWr57.js";import"./index-C34G4LcC.js";import"./index-DqgOudlf.js";import"./index-BmUMhLcl.js";import"./index-C-UhNskA.js";var gt=function(t){var a=t.dt;return`
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
`)},xt={root:function(t){var a=t.props;return["p-tabs p-component",{"p-tabs-scrollable":a.scrollable}]}},wt=S.extend({name:"tabs",theme:gt,classes:xt}),yt={name:"BaseTabs",extends:A,props:{value:{type:String,default:void 0},lazy:{type:Boolean,default:!1},scrollable:{type:Boolean,default:!1},showNavigators:{type:Boolean,default:!0},tabindex:{type:Number,default:0},selectOnFocus:{type:Boolean,default:!1}},style:wt,provide:function(){return{$pcTabs:this,$parentInstance:this}}},q={name:"Tabs",extends:yt,inheritAttrs:!1,emits:["update:value"],data:function(){return{id:this.$attrs.id,d_value:this.value}},watch:{"$attrs.id":function(t){this.id=t||I()},value:function(t){this.d_value=t}},mounted:function(){this.id=this.id||I()},methods:{updateValue:function(t){this.d_value!==t&&(this.d_value=t,this.$emit("update:value",t))},isVertical:function(){return this.orientation==="vertical"}}};function kt(e,t,a,n,d,o){return b(),p("div",v({class:e.cx("root")},e.ptmi("root")),[x(e.$slots,"default")],16)}q.render=kt;var $t={root:"p-tablist",content:function(t){var a=t.instance;return["p-tablist-content",{"p-tablist-viewport":a.$pcTabs.scrollable}]},tabList:"p-tablist-tab-list",activeBar:"p-tablist-active-bar",prevButton:"p-tablist-prev-button p-tablist-nav-button",nextButton:"p-tablist-next-button p-tablist-nav-button"},Tt=S.extend({name:"tablist",classes:$t}),Bt={name:"BaseTabList",extends:A,props:{},style:Tt,provide:function(){return{$pcTabList:this,$parentInstance:this}}},J={name:"TabList",extends:Bt,inheritAttrs:!1,inject:["$pcTabs"],data:function(){return{isPrevButtonEnabled:!1,isNextButtonEnabled:!0}},resizeObserver:void 0,watch:{showNavigators:function(t){t?this.bindResizeObserver():this.unbindResizeObserver()},activeValue:{flush:"post",handler:function(){this.updateInkBar()}}},mounted:function(){var t=this;this.$nextTick(function(){t.updateInkBar()}),this.showNavigators&&(this.updateButtonState(),this.bindResizeObserver())},updated:function(){this.showNavigators&&this.updateButtonState()},beforeUnmount:function(){this.unbindResizeObserver()},methods:{onScroll:function(t){this.showNavigators&&this.updateButtonState(),t.preventDefault()},onPrevButtonClick:function(){var t=this.$refs.content,a=y(t),n=t.scrollLeft-a;t.scrollLeft=n<=0?0:n},onNextButtonClick:function(){var t=this.$refs.content,a=y(t)-this.getVisibleButtonWidths(),n=t.scrollLeft+a,d=t.scrollWidth-a;t.scrollLeft=n>=d?d:n},bindResizeObserver:function(){var t=this;this.resizeObserver=new ResizeObserver(function(){return t.updateButtonState()}),this.resizeObserver.observe(this.$refs.list)},unbindResizeObserver:function(){var t;(t=this.resizeObserver)===null||t===void 0||t.unobserve(this.$refs.list),this.resizeObserver=void 0},updateInkBar:function(){var t=this.$refs,a=t.content,n=t.inkbar,d=t.tabs,o=E(a,'[data-pc-name="tab"][data-p-active="true"]');this.$pcTabs.isVertical()?(n.style.height=X(o)+"px",n.style.top=k(o).top-k(d).top+"px"):(n.style.width=Y(o)+"px",n.style.left=k(o).left-k(d).left+"px")},updateButtonState:function(){var t=this.$refs,a=t.list,n=t.content,d=n.scrollLeft,o=n.scrollTop,g=n.scrollWidth,L=n.scrollHeight,m=n.offsetWidth,i=n.offsetHeight,r=[y(n),G(n)],f=r[0],Z=r[1];this.$pcTabs.isVertical()?(this.isPrevButtonEnabled=o!==0,this.isNextButtonEnabled=a.offsetHeight>=i&&parseInt(o)!==L-Z):(this.isPrevButtonEnabled=d!==0,this.isNextButtonEnabled=a.offsetWidth>=m&&parseInt(d)!==g-f)},getVisibleButtonWidths:function(){var t=this.$refs,a=t.prevBtn,n=t.nextBtn;return[a,n].reduce(function(d,o){return o?d+y(o):d},0)}},computed:{templates:function(){return this.$pcTabs.$slots},activeValue:function(){return this.$pcTabs.d_value},showNavigators:function(){return this.$pcTabs.scrollable&&this.$pcTabs.showNavigators},prevButtonAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.previous:void 0},nextButtonAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.next:void 0}},components:{ChevronLeftIcon:it,ChevronRightIcon:ut},directives:{ripple:M}},_t=["aria-label","tabindex"],Ct=["aria-orientation"],St=["aria-label","tabindex"];function At(e,t,a,n,d,o){var g=U("ripple");return b(),p("div",v({ref:"list",class:e.cx("root")},e.ptmi("root")),[o.showNavigators&&d.isPrevButtonEnabled?K((b(),p("button",v({key:0,ref:"prevButton",class:e.cx("prevButton"),"aria-label":o.prevButtonAriaLabel,tabindex:o.$pcTabs.tabindex,onClick:t[0]||(t[0]=function(){return o.onPrevButtonClick&&o.onPrevButtonClick.apply(o,arguments)})},e.ptm("prevButton"),{"data-pc-group-section":"navigator"}),[(b(),w(R(o.templates.previcon||"ChevronLeftIcon"),v({"aria-hidden":"true"},e.ptm("prevIcon")),null,16))],16,_t)),[[g]]):z("",!0),s("div",v({ref:"content",class:e.cx("content"),onScroll:t[1]||(t[1]=function(){return o.onScroll&&o.onScroll.apply(o,arguments)})},e.ptm("content")),[s("div",v({ref:"tabs",class:e.cx("tabList"),role:"tablist","aria-orientation":o.$pcTabs.orientation||"horizontal"},e.ptm("tabList")),[x(e.$slots,"default"),s("span",v({ref:"inkbar",class:e.cx("activeBar"),role:"presentation","aria-hidden":"true"},e.ptm("activeBar")),null,16)],16,Ct)],16),o.showNavigators&&d.isNextButtonEnabled?K((b(),p("button",v({key:1,ref:"nextButton",class:e.cx("nextButton"),"aria-label":o.nextButtonAriaLabel,tabindex:o.$pcTabs.tabindex,onClick:t[2]||(t[2]=function(){return o.onNextButtonClick&&o.onNextButtonClick.apply(o,arguments)})},e.ptm("nextButton"),{"data-pc-group-section":"navigator"}),[(b(),w(R(o.templates.nexticon||"ChevronRightIcon"),v({"aria-hidden":"true"},e.ptm("nextIcon")),null,16))],16,St)),[[g]]):z("",!0)],16)}J.render=At;var Lt={root:function(t){var a=t.instance,n=t.props;return["p-tab",{"p-tab-active":a.active,"p-disabled":n.disabled}]}},Pt=S.extend({name:"tab",classes:Lt}),Nt={name:"BaseTab",extends:A,props:{value:{type:String,default:void 0},disabled:{type:Boolean,default:!1},as:{type:String,default:"BUTTON"},asChild:{type:Boolean,default:!1}},style:Pt,provide:function(){return{$pcTab:this,$parentInstance:this}}},C={name:"Tab",extends:Nt,inheritAttrs:!1,inject:["$pcTabs","$pcTabList"],methods:{onFocus:function(){this.$pcTabs.selectOnFocus&&this.changeActiveValue()},onClick:function(){this.changeActiveValue()},onKeydown:function(t){switch(t.code){case"ArrowRight":this.onArrowRightKey(t);break;case"ArrowLeft":this.onArrowLeftKey(t);break;case"Home":this.onHomeKey(t);break;case"End":this.onEndKey(t);break;case"PageDown":this.onPageDownKey(t);break;case"PageUp":this.onPageUpKey(t);break;case"Enter":case"NumpadEnter":case"Space":this.onEnterKey(t);break}},onArrowRightKey:function(t){var a=this.findNextTab(t.currentTarget);a?this.changeFocusedTab(t,a):this.onHomeKey(t),t.preventDefault()},onArrowLeftKey:function(t){var a=this.findPrevTab(t.currentTarget);a?this.changeFocusedTab(t,a):this.onEndKey(t),t.preventDefault()},onHomeKey:function(t){var a=this.findFirstTab();this.changeFocusedTab(t,a),t.preventDefault()},onEndKey:function(t){var a=this.findLastTab();this.changeFocusedTab(t,a),t.preventDefault()},onPageDownKey:function(t){this.scrollInView(this.findLastTab()),t.preventDefault()},onPageUpKey:function(t){this.scrollInView(this.findFirstTab()),t.preventDefault()},onEnterKey:function(t){this.changeActiveValue(),t.preventDefault()},findNextTab:function(t){var a=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1,n=a?t:t.nextElementSibling;return n?$(n,"data-p-disabled")||$(n,"data-pc-section")==="inkbar"?this.findNextTab(n):E(n,'[data-pc-name="tab"]'):null},findPrevTab:function(t){var a=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1,n=a?t:t.previousElementSibling;return n?$(n,"data-p-disabled")||$(n,"data-pc-section")==="inkbar"?this.findPrevTab(n):E(n,'[data-pc-name="tab"]'):null},findFirstTab:function(){return this.findNextTab(this.$pcTabList.$refs.content.firstElementChild,!0)},findLastTab:function(){return this.findPrevTab(this.$pcTabList.$refs.content.lastElementChild,!0)},changeActiveValue:function(){this.$pcTabs.updateValue(this.value)},changeFocusedTab:function(t,a){et(a),this.scrollInView(a)},scrollInView:function(t){var a;t==null||(a=t.scrollIntoView)===null||a===void 0||a.call(t,{block:"nearest"})}},computed:{active:function(){var t;return tt((t=this.$pcTabs)===null||t===void 0?void 0:t.d_value,this.value)},id:function(){var t;return"".concat((t=this.$pcTabs)===null||t===void 0?void 0:t.id,"_tab_").concat(this.value)},ariaControls:function(){var t;return"".concat((t=this.$pcTabs)===null||t===void 0?void 0:t.id,"_tabpanel_").concat(this.value)},attrs:function(){return v(this.asAttrs,this.a11yAttrs,this.ptmi("root",this.ptParams))},asAttrs:function(){return this.as==="BUTTON"?{type:"button",disabled:this.disabled}:void 0},a11yAttrs:function(){return{id:this.id,tabindex:this.active?this.$pcTabs.tabindex:-1,role:"tab","aria-selected":this.active,"aria-controls":this.ariaControls,"data-pc-name":"tab","data-p-disabled":this.disabled,"data-p-active":this.active,onFocus:this.onFocus,onKeydown:this.onKeydown}},ptParams:function(){return{context:{active:this.active}}}},directives:{ripple:M}};function Vt(e,t,a,n,d,o){var g=U("ripple");return e.asChild?x(e.$slots,"default",{key:1,class:_(e.cx("root")),active:o.active,a11yAttrs:o.a11yAttrs,onClick:o.onClick}):K((b(),w(R(e.as),v({key:0,class:e.cx("root"),onClick:o.onClick},o.attrs),{default:u(function(){return[x(e.$slots,"default")]}),_:3},16,["class","onClick"])),[[g]])}C.render=Vt;var Dt={root:"p-tabpanels"},Et=S.extend({name:"tabpanels",classes:Dt}),Kt={name:"BaseTabPanels",extends:A,props:{},style:Et,provide:function(){return{$pcTabPanels:this,$parentInstance:this}}},Q={name:"TabPanels",extends:Kt,inheritAttrs:!1};function Rt(e,t,a,n,d,o){return b(),p("div",v({class:e.cx("root"),role:"presentation"},e.ptmi("root")),[x(e.$slots,"default")],16)}Q.render=Rt;const zt={class:"min-h-screen bg-[#F8FAFC] pb-20 font-sans"},It={class:"bg-[#0F172A] pt-12 pb-28 px-8 relative overflow-hidden"},Ft={class:"max-w-[1500px] mx-auto relative z-10 flex flex-col md:flex-row justify-between items-center gap-8"},Ot={class:"flex flex-col md:flex-row items-center gap-8 text-center md:text-left"},jt={class:"relative group"},Ht={class:"text-4xl font-black text-white tracking-tighter italic uppercase leading-none"},Wt={class:"text-slate-400 mt-2 font-medium text-sm italic"},Ut={class:"flex gap-4"},Mt={class:"max-w-[1500px] mx-auto px-8 -mt-16 relative z-20 space-y-8"},qt={key:0,class:"grid grid-cols-1 md:grid-cols-4 gap-6"},Jt={key:1,class:"grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6"},Qt={class:"flex justify-between items-start mb-4"},Zt={class:"text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1"},Gt={class:"text-3xl font-black text-slate-900 tracking-tighter italic"},Xt={class:"bg-white rounded-[1rem] border border-slate-200 shadow-2xl overflow-hidden min-h-[600px]"},Yt={class:"grid grid-cols-1 lg:grid-cols-3 gap-10"},te={class:"lg:col-span-2 space-y-6"},ee={class:"h-[400px] p-6 rounded-[1rem] bg-slate-50 border border-slate-100 shadow-inner"},ae={class:"space-y-6"},se={class:"h-[400px] flex items-center justify-center p-6 rounded-[1rem] border border-red-50"},ne={class:"space-y-8"},oe={class:"flex justify-between items-center mb-6"},ie={class:"bg-[#0F172A] px-8 py-5 flex justify-between items-center"},re={class:"font-black text-white text-sm tracking-tight italic uppercase"},le={class:"text-[10px] font-black text-red-500 uppercase tracking-[0.2em]"},ce={class:"font-black text-slate-800 tracking-tight italic"},de={key:0,class:"w-32"},ue={class:"flex justify-between mb-1"},be={class:"text-xs font-black text-slate-800"},pe={key:1,class:"text-[10px] italic text-slate-300"},fe={class:"text-xs font-bold text-slate-500 italic"},he={class:"max-w-3xl mx-auto py-10 px-6 bg-slate-50 rounded-[1rem] border border-slate-100"},ve={class:"p-6 bg-white rounded-3xl shadow-sm border border-slate-200 mb-6 group hover:shadow-lg transition-all"},me={class:"text-sm font-black text-slate-800 italic uppercase mb-1"},ge={class:"text-xs text-slate-500 font-medium leading-relaxed mb-3"},xe={class:"text-[10px] font-black text-slate-400 tracking-widest italic"},we=at({__name:"StudentReport",props:{student:{},globalStats:{},coursesData:{},performanceOverTime:{},skillsData:{},activityLog:{}},setup(e){const t=e;st();const a=O(!0),n=O("");nt(()=>{setTimeout(()=>{a.value=!1},600)});const d=V(()=>{if(!n.value)return t.coursesData;const m=n.value.toLowerCase();return t.coursesData.map(i=>({...i,exams:i.exams.filter(r=>r.title.toLowerCase().includes(m))})).filter(i=>i.exams.length>0||i.title.toLowerCase().includes(m))}),o=V(()=>({labels:t.performanceOverTime.labels,datasets:[{label:"Performance (%)",data:t.performanceOverTime.scores,fill:!0,borderColor:"#E11D48",backgroundColor:"rgba(225, 29, 72, 0.1)",tension:.4,pointBackgroundColor:"#fff",pointBorderColor:"#E11D48",pointRadius:5}]})),g=V(()=>{var m,i;return{labels:((m=t.skillsData)==null?void 0:m.labels)||[],datasets:[{label:"Maîtrise",data:((i=t.skillsData)==null?void 0:i.scores)||[],backgroundColor:"rgba(225, 29, 72, 0.2)",borderColor:"#E11D48",pointBackgroundColor:"#E11D48"}]}}),L=m=>({Réussi:{sev:"success",icon:"pi-check-circle"},Échoué:{sev:"danger",icon:"pi-times"},"En attente":{sev:"warn",icon:"pi-clock"}})[m]||{sev:"secondary",icon:"pi-info"};return(m,i)=>(b(),w(rt,null,{default:u(()=>[c(l(ot),{title:`Analytics | ${e.student.name}`},null,8,["title"]),s("div",zt,[s("header",It,[i[3]||(i[3]=s("div",{class:"absolute top-[-10%] right-[-5%] w-[600px] h-[600px] bg-red-600/15 rounded-full blur-[120px] pointer-events-none"},null,-1)),i[4]||(i[4]=s("div",{class:"absolute bottom-[-20%] left-[-5%] w-[400px] h-[400px] bg-red-900/10 rounded-full blur-[100px] pointer-events-none"},null,-1)),s("div",Ft,[s("div",Ot,[s("div",jt,[c(l(lt),{image:e.student.avatar,label:e.student.name.charAt(0),size:"xlarge",shape:"circle",class:"!w-24 !h-24 !bg-red-50 !text-red-600 !font-black border-4 border-white/10 shadow-2xl group-hover:scale-105 transition-transform"},null,8,["image","label"]),i[1]||(i[1]=s("div",{class:"absolute bottom-1 right-1 w-5 h-5 bg-emerald-500 border-2 border-[#0F172A] rounded-full"},null,-1))]),s("div",null,[i[2]||(i[2]=s("div",{class:"flex items-center justify-center md:justify-start gap-3 mb-2"},[s("span",{class:"bg-red-500/10 text-red-500 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border border-red-500/20"},"Student Analytics Profile")],-1)),s("h1",Ht,h(e.student.name),1),s("p",Wt,h(e.student.email),1)])]),s("div",Ut,[c(l(W),{label:"Rapport PDF",icon:"pi pi-file-pdf",class:"!bg-white/5 hover:!bg-white/10 !border-white/10 !text-white !rounded-xl !px-6 !py-3 backdrop-blur-xl"}),c(l(W),{label:"Partager",icon:"pi pi-share-alt",class:"!bg-red-600 hover:!bg-red-700 !border-none !rounded-xl !px-6 !py-3 shadow-xl shadow-red-600/20 transition-all active:scale-95"})])])]),s("main",Mt,[a.value?(b(),p("div",qt,[(b(),p(P,null,N(4,r=>c(l(ft),{height:"120px",borderRadius:"32px",key:r})),64))])):(b(),p("section",Jt,[(b(!0),p(P,null,N([{l:"Score Moyen",v:e.globalStats.average_score+"%",t:e.globalStats.score_trend,i:"pi-percentage",c:"text-red-600",bg:"bg-red-50"},{l:"Taux de Réussite",v:e.globalStats.success_rate+"%",t:e.globalStats.success_trend,i:"pi-verified",c:"text-emerald-600",bg:"bg-emerald-50"},{l:"Examen Validés",v:e.globalStats.completed_exams,t:e.globalStats.success_trend,i:"pi-book",c:"text-blue-600",bg:"bg-blue-50"},{l:"Total Tentatives",v:e.globalStats.total_attempts,t:e.globalStats.success_trend,i:"pi-sync",c:"text-slate-900",bg:"bg-slate-100"}],r=>(b(),p("div",{key:r.l,class:"bg-white p-6 rounded-[1rem] border border-slate-200/50 shadow-sm hover:shadow-xl transition-all group"},[s("div",Qt,[s("div",{class:_([r.bg,"w-10 h-10 rounded-xl flex items-center justify-center group-hover:rotate-6 transition-transform"])},[s("i",{class:_(["pi",r.i,r.c,"text-lg"])},null,2)],2),r.t!==void 0&&r.t!==null?(b(),w(l(H),{key:0,severity:Number(r.t)>=0?"success":"danger",value:`${Number(r.t)>0?"+":""}${r.t}%`,class:"!text-[9px] !font-black"},null,8,["severity","value"])):z("",!0)]),s("p",Zt,h(r.l),1),s("h2",Gt,h(r.v),1)]))),128))])),s("div",Xt,[c(l(q),{value:"0"},{default:u(()=>[c(l(J),{class:"bg-slate-50/50 px-8 pt-4 !border-none"},{default:u(()=>[c(l(C),{value:"0",class:"!text-xs !font-black !uppercase !tracking-widest !px-8"},{default:u(()=>[...i[5]||(i[5]=[T("Performance",-1)])]),_:1}),c(l(C),{value:"1",class:"!text-xs !font-black !uppercase !tracking-widest !px-8"},{default:u(()=>[...i[6]||(i[6]=[T("Relevé de notes",-1)])]),_:1}),c(l(C),{value:"2",class:"!text-xs !font-black !uppercase !tracking-widest !px-8"},{default:u(()=>[...i[7]||(i[7]=[T("Timeline",-1)])]),_:1})]),_:1}),c(l(Q),{class:"p-8"},{default:u(()=>[c(l(D),{value:"0"},{default:u(()=>[s("div",Yt,[s("div",te,[i[8]||(i[8]=s("h3",{class:"text-sm font-black text-slate-800 uppercase tracking-widest flex items-center gap-2"},[s("span",{class:"w-2 h-2 bg-red-600 rounded-full animate-pulse"}),T(" Évolution Cognitive ")],-1)),s("div",ee,[c(l(j),{type:"line",data:o.value,class:"h-full",options:{maintainAspectRatio:!1,plugins:{legend:{display:!1}}}},null,8,["data"])])]),s("div",ae,[i[9]||(i[9]=s("h3",{class:"text-sm font-black text-slate-800 uppercase tracking-widest text-center"},"Skill Mapping",-1)),s("div",se,[c(l(j),{type:"radar",data:g.value,class:"h-full",options:{maintainAspectRatio:!1,plugins:{legend:{display:!1}}}},null,8,["data"])])])])]),_:1}),c(l(D),{value:"1"},{default:u(()=>[s("div",ne,[s("div",oe,[c(l(ht),{iconPosition:"left",class:"w-full max-w-md"},{default:u(()=>[c(l(vt),{class:"pi pi-search"}),c(l(bt),{modelValue:n.value,"onUpdate:modelValue":i[0]||(i[0]=r=>n.value=r),placeholder:"Rechercher une évaluation...",class:"w-full !rounded-xl !bg-slate-50 border-none !py-4"},null,8,["modelValue"])]),_:1})]),(b(!0),p(P,null,N(d.value,r=>(b(),p("div",{key:r.id,class:"rounded-[1rem] border border-slate-100 overflow-hidden mb-8 shadow-sm"},[s("div",ie,[s("h4",re,h(r.title),1),s("span",le,h(r.exams.length)+" Assessments",1)]),c(l(ct),{value:r.exams,responsiveLayout:"scroll",class:"p-datatable-v40"},{default:u(()=>[c(l(B),{field:"title",header:"Évaluation"},{body:u(({data:f})=>[s("span",ce,h(f.title),1)]),_:1}),c(l(B),{header:"Score (%)"},{body:u(({data:f})=>[f.score!==null?(b(),p("div",de,[s("div",ue,[i[10]||(i[10]=s("span",{class:"text-[10px] font-bold text-slate-400"},"Précision",-1)),s("span",be,h(f.score)+"%",1)]),c(l(dt),{value:f.score,showValue:!1,class:"h-1 bg-slate-100",style:F(`--p-progressbar-value-background: ${f.score>=50?"#10B981":"#E11D48"}`)},null,8,["value","style"])])):(b(),p("span",pe,"Pending"))]),_:1}),c(l(B),{header:"Statut"},{body:u(({data:f})=>[c(l(H),{severity:L(f.status).sev,value:f.status,class:"!text-[9px] !font-black !uppercase !px-3 !rounded-lg"},null,8,["severity","value"])]),_:1}),c(l(B),{header:"Date"},{body:u(({data:f})=>[s("span",fe,h(f.attempted_at||"---"),1)]),_:1})]),_:1},8,["value"])]))),128))])]),_:1}),c(l(D),{value:"2"},{default:u(()=>[s("div",he,[c(l(pt),{value:e.activityLog,align:"alternate",class:"custom-timeline-v40"},{marker:u(r=>[s("div",{class:"w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-xl",style:F({backgroundColor:r.item.color})},[s("i",{class:_([r.item.icon,"text-sm"])},null,2)],4)]),content:u(r=>[s("div",ve,[s("h4",me,h(r.item.status),1),s("p",ge,h(r.item.description),1),s("span",xe,h(r.item.date),1)])]),_:1},8,["value"])])]),_:1})]),_:1})]),_:1})])])])]),_:1}))}}),ea=mt(we,[["__scopeId","data-v-2f7b3425"]]);export{ea as default};
