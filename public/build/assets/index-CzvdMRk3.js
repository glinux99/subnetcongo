import{J as v,M as r,o as i,f as s,w as h,l as C,ak as u,b as D,L as p,v as H,g as m,Q as F,n as f,as as N,ab as $,a9 as _,aj as E,a4 as T,K as P,c as y,F as U,e as L,a as k,t as j}from"./app-CmOH-1_J.js";import{s as z}from"./index-D9ABT2bB.js";import{b as I}from"./AppLayout-X_3DTF5x.js";import{s as g}from"./index-iyK7KXSc.js";import{c as O}from"./index-DF3QjxUT.js";import{R}from"./index-DfX9Bp0U.js";var V={root:"p-accordioncontent",content:"p-accordioncontent-content"},M=v.extend({name:"accordioncontent",classes:V}),q={name:"BaseAccordionContent",extends:g,props:{as:{type:String,default:"DIV"},asChild:{type:Boolean,default:!1}},style:M,provide:function(){return{$pcAccordionContent:this,$parentInstance:this}}},w={name:"AccordionContent",extends:q,inheritAttrs:!1,inject:["$pcAccordion","$pcAccordionPanel"],computed:{id:function(){return"".concat(this.$pcAccordion.id,"_accordioncontent_").concat(this.$pcAccordionPanel.value)},ariaLabelledby:function(){return"".concat(this.$pcAccordion.id,"_accordionheader_").concat(this.$pcAccordionPanel.value)},attrs:function(){return r(this.a11yAttrs,this.ptmi("root",this.ptParams))},a11yAttrs:function(){return{id:this.id,role:"region","aria-labelledby":this.ariaLabelledby,"data-pc-name":"accordioncontent","data-p-active":this.$pcAccordionPanel.active}},ptParams:function(){return{context:{active:this.$pcAccordionPanel.active}}}}};function J(e,n,o,a,d,t){return e.asChild?p(e.$slots,"default",{key:1,class:f(e.cx("root")),active:t.$pcAccordionPanel.active,a11yAttrs:t.a11yAttrs}):(i(),s(F,r({key:0,name:"p-toggleable-content"},e.ptm("transition",t.ptParams)),{default:h(function(){return[!t.$pcAccordion.lazy||t.$pcAccordionPanel.active?C((i(),s(u(e.as),r({key:0,class:e.cx("root")},t.attrs),{default:h(function(){return[D("div",r({class:e.cx("content")},e.ptm("content",t.ptParams)),[p(e.$slots,"default")],16)]}),_:3},16,["class"])),[[H,t.$pcAccordion.lazy?!0:t.$pcAccordionPanel.active]]):m("",!0)]}),_:3},16))}w.render=J;var Q={root:"p-accordionheader",toggleicon:"p-accordionheader-toggle-icon"},G=v.extend({name:"accordionheader",classes:Q}),W={name:"BaseAccordionHeader",extends:g,props:{as:{type:String,default:"BUTTON"},asChild:{type:Boolean,default:!1}},style:G,provide:function(){return{$pcAccordionHeader:this,$parentInstance:this}}},x={name:"AccordionHeader",extends:W,inheritAttrs:!1,inject:["$pcAccordion","$pcAccordionPanel"],methods:{onFocus:function(){this.$pcAccordion.selectOnFocus&&this.changeActiveValue()},onClick:function(){this.changeActiveValue()},onKeydown:function(n){switch(n.code){case"ArrowDown":this.onArrowDownKey(n);break;case"ArrowUp":this.onArrowUpKey(n);break;case"Home":this.onHomeKey(n);break;case"End":this.onEndKey(n);break;case"Enter":case"NumpadEnter":case"Space":this.onEnterKey(n);break}},onArrowDownKey:function(n){var o=this.findNextPanel(this.findPanel(n.currentTarget));o?this.changeFocusedPanel(n,o):this.onHomeKey(n),n.preventDefault()},onArrowUpKey:function(n){var o=this.findPrevPanel(this.findPanel(n.currentTarget));o?this.changeFocusedPanel(n,o):this.onEndKey(n),n.preventDefault()},onHomeKey:function(n){var o=this.findFirstPanel();this.changeFocusedPanel(n,o),n.preventDefault()},onEndKey:function(n){var o=this.findLastPanel();this.changeFocusedPanel(n,o),n.preventDefault()},onEnterKey:function(n){this.changeActiveValue(),n.preventDefault()},findPanel:function(n){return n==null?void 0:n.closest('[data-pc-name="accordionpanel"]')},findHeader:function(n){return _(n,'[data-pc-name="accordionheader"]')},findNextPanel:function(n){var o=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1,a=o?n:n.nextElementSibling;return a?$(a,"data-p-disabled")?this.findNextPanel(a):this.findHeader(a):null},findPrevPanel:function(n){var o=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1,a=o?n:n.previousElementSibling;return a?$(a,"data-p-disabled")?this.findPrevPanel(a):this.findHeader(a):null},findFirstPanel:function(){return this.findNextPanel(this.$pcAccordion.$el.firstElementChild,!0)},findLastPanel:function(){return this.findPrevPanel(this.$pcAccordion.$el.lastElementChild,!0)},changeActiveValue:function(){this.$pcAccordion.updateValue(this.$pcAccordionPanel.value)},changeFocusedPanel:function(n,o){N(this.findHeader(o))}},computed:{id:function(){return"".concat(this.$pcAccordion.id,"_accordionheader_").concat(this.$pcAccordionPanel.value)},ariaControls:function(){return"".concat(this.$pcAccordion.id,"_accordioncontent_").concat(this.$pcAccordionPanel.value)},attrs:function(){return r(this.asAttrs,this.a11yAttrs,this.ptmi("root",this.ptParams))},asAttrs:function(){return this.as==="BUTTON"?{type:"button",disabled:this.$pcAccordionPanel.disabled}:void 0},a11yAttrs:function(){return{id:this.id,tabindex:this.$pcAccordion.tabindex,"aria-expanded":this.$pcAccordionPanel.active,"aria-controls":this.ariaControls,"data-pc-name":"accordionheader","data-p-disabled":this.$pcAccordionPanel.disabled,"data-p-active":this.$pcAccordionPanel.active,onFocus:this.onFocus,onKeydown:this.onKeydown}},ptParams:function(){return{context:{active:this.$pcAccordionPanel.active}}}},components:{ChevronUpIcon:I,ChevronDownIcon:O},directives:{ripple:R}};function X(e,n,o,a,d,t){var A=E("ripple");return e.asChild?p(e.$slots,"default",{key:1,class:f(e.cx("root")),active:t.$pcAccordionPanel.active,a11yAttrs:t.a11yAttrs,onClick:t.onClick}):C((i(),s(u(e.as),r({key:0,class:e.cx("root"),onClick:t.onClick},t.attrs),{default:h(function(){return[p(e.$slots,"default",{active:t.$pcAccordionPanel.active}),p(e.$slots,"toggleicon",{active:t.$pcAccordionPanel.active,class:f(e.cx("toggleicon"))},function(){return[t.$pcAccordionPanel.active?(i(),s(u(t.$pcAccordion.$slots.collapseicon?t.$pcAccordion.$slots.collapseicon:t.$pcAccordion.collapseIcon?"span":"ChevronDownIcon"),r({key:0,class:[t.$pcAccordion.collapseIcon,e.cx("toggleicon")],"aria-hidden":"true"},e.ptm("toggleicon",t.ptParams)),null,16,["class"])):(i(),s(u(t.$pcAccordion.$slots.expandicon?t.$pcAccordion.$slots.expandicon:t.$pcAccordion.expandIcon?"span":"ChevronUpIcon"),r({key:1,class:[t.$pcAccordion.expandIcon,e.cx("toggleicon")],"aria-hidden":"true"},e.ptm("toggleicon",t.ptParams)),null,16,["class"]))]})]}),_:3},16,["class","onClick"])),[[A]])}x.render=X;var Y={root:function(n){var o=n.instance,a=n.props;return["p-accordionpanel",{"p-accordionpanel-active":o.active,"p-disabled":a.disabled}]}},Z=v.extend({name:"accordionpanel",classes:Y}),ee={name:"BaseAccordionPanel",extends:g,props:{value:{type:String,default:void 0},disabled:{type:Boolean,default:!1},as:{type:String,default:"DIV"},asChild:{type:Boolean,default:!1}},style:Z,provide:function(){return{$pcAccordionPanel:this,$parentInstance:this}}},K={name:"AccordionPanel",extends:ee,inheritAttrs:!1,inject:["$pcAccordion"],computed:{active:function(){return this.$pcAccordion.isItemActive(this.value)},attrs:function(){return r(this.a11yAttrs,this.ptmi("root",this.ptParams))},a11yAttrs:function(){return{"data-pc-name":"accordionpanel","data-p-disabled":this.disabled,"data-p-active":this.active}},ptParams:function(){return{context:{active:this.active}}}}};function ne(e,n,o,a,d,t){return e.asChild?p(e.$slots,"default",{key:1,class:f(e.cx("root")),active:t.active,a11yAttrs:t.a11yAttrs}):(i(),s(u(e.as),r({key:0,class:e.cx("root")},t.attrs),{default:h(function(){return[p(e.$slots,"default")]}),_:3},16,["class"]))}K.render=ne;var oe=function(n){var o=n.dt;return`
.p-accordionpanel {
    display: flex;
    flex-direction: column;
    border-style: solid;
    border-width: `.concat(o("accordion.panel.border.width"),`;
    border-color: `).concat(o("accordion.panel.border.color"),`;
}

.p-accordionheader {
    all: unset;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: `).concat(o("accordion.header.padding"),`;
    color: `).concat(o("accordion.header.color"),`;
    background: `).concat(o("accordion.header.background"),`;
    border-style: solid;
    border-width: `).concat(o("accordion.header.border.width"),`;
    border-color: `).concat(o("accordion.header.border.color"),`;
    font-weight: `).concat(o("accordion.header.font.weight"),`;
    border-radius: `).concat(o("accordion.header.border.radius"),`;
    transition: background `).concat(o("accordion.transition.duration"),"; color ").concat(o("accordion.transition.duration"),"color ").concat(o("accordion.transition.duration"),", outline-color ").concat(o("accordion.transition.duration"),", box-shadow ").concat(o("accordion.transition.duration"),`;
    outline-color: transparent;
}

.p-accordionpanel:first-child > .p-accordionheader {
    border-width: `).concat(o("accordion.header.first.border.width"),`;
    border-top-left-radius: `).concat(o("accordion.header.first.top.border.radius"),`;
    border-top-right-radius: `).concat(o("accordion.header.first.top.border.radius"),`;
}

.p-accordionpanel:last-child > .p-accordionheader {
    border-bottom-left-radius: `).concat(o("accordion.header.last.bottom.border.radius"),`;
    border-bottom-right-radius: `).concat(o("accordion.header.last.bottom.border.radius"),`;
}

.p-accordionpanel:last-child.p-accordionpanel-active > .p-accordionheader {
    border-bottom-left-radius: `).concat(o("accordion.header.last.active.bottom.border.radius"),`;
    border-bottom-right-radius:`).concat(o("accordion.header.last.active.bottom.border.radius"),`;
}

.p-accordionheader-toggle-icon {
    color: `).concat(o("accordion.header.toggle.icon.color"),`;
}

.p-accordionpanel:not(.p-disabled) .p-accordionheader:focus-visible {
    box-shadow: `).concat(o("accordion.header.focus.ring.shadow"),`;
    outline: `).concat(o("accordion.header.focus.ring.width")," ").concat(o("accordion.header.focus.ring.style")," ").concat(o("accordion.header.focus.ring.color"),`;
    outline-offset: `).concat(o("accordion.header.focus.ring.offset"),`;
}

.p-accordionpanel:not(.p-accordionpanel-active):not(.p-disabled) > .p-accordionheader:hover {
    background: `).concat(o("accordion.header.hover.background"),`;
    color: `).concat(o("accordion.header.hover.color"),`
}

.p-accordionpanel:not(.p-accordionpanel-active):not(.p-disabled) .p-accordionheader:hover .p-accordionheader-toggle-icon {
    color: `).concat(o("accordion.header.toggle.icon.hover.color"),`;
}

.p-accordionpanel:not(.p-disabled).p-accordionpanel-active > .p-accordionheader {
    background: `).concat(o("accordion.header.active.background"),`;
    color: `).concat(o("accordion.header.active.color"),`
}

.p-accordionpanel:not(.p-disabled).p-accordionpanel-active > .p-accordionheader .p-accordionheader-toggle-icon {
    color: `).concat(o("accordion.header.toggle.icon.active.color"),`;
}

.p-accordionpanel:not(.p-disabled).p-accordionpanel-active > .p-accordionheader:hover  {
    background: `).concat(o("accordion.header.active.hover.background"),`;
    color: `).concat(o("accordion.header.active.hover.color"),`
}

.p-accordionpanel:not(.p-disabled).p-accordionpanel-active > .p-accordionheader:hover  .p-accordionheader-toggle-icon {
    color: `).concat(o("accordion.header.toggle.icon.active.hover.color"),`;
}

.p-accordioncontent-content {
    border-style: solid;
    border-width: `).concat(o("accordion.content.border.width"),`;
    border-color: `).concat(o("accordion.content.border.color"),`;
    background-color: `).concat(o("accordion.content.background"),`;
    color: `).concat(o("accordion.content.color"),`;
    padding: `).concat(o("accordion.content.padding"),`
}
`)},te={root:"p-accordion p-component"},ae=v.extend({name:"accordion",theme:oe,classes:te}),ce={name:"BaseAccordion",extends:g,props:{value:{type:[String,Array],default:void 0},multiple:{type:Boolean,default:!1},lazy:{type:Boolean,default:!1},tabindex:{type:Number,default:0},selectOnFocus:{type:Boolean,default:!1},expandIcon:{type:String,default:void 0},collapseIcon:{type:String,default:void 0},activeIndex:{type:[Number,Array],default:null}},style:ae,provide:function(){return{$pcAccordion:this,$parentInstance:this}}},re={name:"Accordion",extends:ce,inheritAttrs:!1,emits:["update:value","update:activeIndex","tab-open","tab-close","tab-click"],data:function(){return{id:this.$attrs.id,d_value:this.value}},watch:{"$attrs.id":function(n){this.id=n||T()},value:function(n){this.d_value=n},activeIndex:{immediate:!0,handler:function(n){this.hasAccordionTab&&(this.d_value=this.multiple?n==null?void 0:n.map(String):n==null?void 0:n.toString())}}},mounted:function(){this.id=this.id||T()},methods:{isItemActive:function(n){var o;return this.multiple?(o=this.d_value)===null||o===void 0?void 0:o.includes(n):this.d_value===n},updateValue:function(n){var o,a=this.isItemActive(n);this.multiple?a?this.d_value=this.d_value.filter(function(d){return d!==n}):this.d_value?this.d_value.push(n):this.d_value=[n]:this.d_value=a?null:n,this.$emit("update:value",this.d_value),this.$emit("update:activeIndex",this.multiple?(o=this.d_value)===null||o===void 0?void 0:o.map(Number):Number(this.d_value)),this.$emit(a?"tab-close":"tab-open",{originalEvent:void 0,index:Number(n)})},isAccordionTab:function(n){return n.type.name==="AccordionTab"},getTabProp:function(n,o){return n.props?n.props[o]:void 0},getKey:function(n,o){return this.getTabProp(n,"header")||o},getHeaderPT:function(n,o){var a=this;return{root:r({onClick:function(t){return a.onTabClick(t,o)}},this.getTabProp(n,"headerProps"),this.getTabPT(n,"header",o)),toggleicon:r(this.getTabProp(n,"headeractionprops"),this.getTabPT(n,"headeraction",o))}},getContentPT:function(n,o){return{root:r(this.getTabProp(n,"contentProps"),this.getTabPT(n,"toggleablecontent",o)),transition:this.getTabPT(n,"transition",o),content:this.getTabPT(n,"content",o)}},getTabPT:function(n,o,a){var d=this.tabs.length,t={props:n.props||{},parent:{instance:this,props:this.$props,state:this.$data},context:{index:a,count:d,first:a===0,last:a===d-1,active:this.isItemActive("".concat(a))}};return r(this.ptm("accordiontab.".concat(o),t),this.ptmo(this.getTabProp(n,"pt"),o,t))},onTabClick:function(n,o){this.$emit("tab-click",{originalEvent:n,index:o})}},computed:{tabs:function(){var n=this;return this.$slots.default().reduce(function(o,a){return n.isAccordionTab(a)?o.push(a):a.children&&a.children instanceof Array&&a.children.forEach(function(d){n.isAccordionTab(d)&&o.push(d)}),o},[])},hasAccordionTab:function(){return this.tabs.length}},components:{AccordionPanel:K,AccordionHeader:x,AccordionContent:w,ChevronUpIcon:I,ChevronRightIcon:z}};function ie(e,n,o,a,d,t){var A=P("AccordionHeader"),S=P("AccordionContent"),B=P("AccordionPanel");return i(),y("div",r({class:e.cx("root")},e.ptmi("root")),[t.hasAccordionTab?(i(!0),y(U,{key:0},L(t.tabs,function(c,l){return i(),s(B,{key:t.getKey(c,l),value:"".concat(l),pt:{root:t.getTabPT(c,"root",l)},disabled:t.getTabProp(c,"disabled")},{default:h(function(){return[k(A,{class:f(t.getTabProp(c,"headerClass")),pt:t.getHeaderPT(c,l)},{toggleicon:h(function(b){return[b.active?(i(),s(u(e.$slots.collapseicon?e.$slots.collapseicon:e.collapseIcon?"span":"ChevronDownIcon"),r({key:0,class:[e.collapseIcon,b.class],"aria-hidden":"true",ref_for:!0},t.getTabPT(c,"headericon",l)),null,16,["class"])):(i(),s(u(e.$slots.expandicon?e.$slots.expandicon:e.expandIcon?"span":"ChevronUpIcon"),r({key:1,class:[e.expandIcon,b.class],"aria-hidden":"true",ref_for:!0},t.getTabPT(c,"headericon",l)),null,16,["class"]))]}),default:h(function(){return[c.children&&c.children.headericon?(i(),s(u(c.children.headericon),{key:0,isTabActive:t.isItemActive("".concat(l)),active:t.isItemActive("".concat(l)),index:l},null,8,["isTabActive","active","index"])):m("",!0),c.props&&c.props.header?(i(),y("span",r({key:1,ref_for:!0},t.getTabPT(c,"headertitle",l)),j(c.props.header),17)):m("",!0),c.children&&c.children.header?(i(),s(u(c.children.header),{key:2})):m("",!0)]}),_:2},1032,["class","pt"]),k(S,{pt:t.getContentPT(c,l)},{default:h(function(){return[(i(),s(u(c)))]}),_:2},1032,["pt"])]}),_:2},1032,["value","pt","disabled"])}),128)):p(e.$slots,"default",{key:1})],16)}re.render=ie;var de=v.extend({name:"accordiontab"}),se={name:"BaseAccordionTab",extends:g,props:{header:null,headerStyle:null,headerClass:null,headerProps:null,headerActionProps:null,contentStyle:null,contentClass:null,contentProps:null,disabled:Boolean},style:de,provide:function(){return{$pcAccordionTab:this,$parentInstance:this}}},le={name:"AccordionTab",extends:se,inheritAttrs:!1,mounted:function(){console.warn("Deprecated since v4. Use the new structure of Accordion instead.")}};function ue(e,n,o,a,d,t){return p(e.$slots,"default")}le.render=ue;export{le as a,re as s};
