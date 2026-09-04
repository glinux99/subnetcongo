import{s as l}from"./index-BZ1ZQmf6.js";import{s as m,a as g,b as d}from"./index-B993g9Uq.js";import{s as p}from"./index-DjX4PZ-M.js";import{K as u,o,c,x as r,g as f,M as i,N as b,h}from"./app-BCrX_6NZ.js";var w=function(s){var n=s.dt;return`
.p-inlinemessage {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: `.concat(n("inlinemessage.padding"),`;
    border-radius: `).concat(n("inlinemessage.border.radius"),`;
    gap: `).concat(n("inlinemessage.gap"),`;
}

.p-inlinemessage-text {
    font-weight: `).concat(n("inlinemessage.text.font.weight"),`;
}

.p-inlinemessage-icon {
    flex-shrink: 0;
    font-size: `).concat(n("inlinemessage.icon.size"),`;
    width: `).concat(n("inlinemessage.icon.size"),`;
    height: `).concat(n("inlinemessage.icon.size"),`;
}

.p-inlinemessage-icon-only .p-inlinemessage-text {
    visibility: hidden;
    width: 0;
}

.p-inlinemessage-info {
    background: `).concat(n("inlinemessage.info.background"),`;
    border: 1px solid `).concat(n("inlinemessage.info.border.color"),`;
    color: `).concat(n("inlinemessage.info.color"),`;
    box-shadow: `).concat(n("inlinemessage.info.shadow"),`;
}

.p-inlinemessage-info .p-inlinemessage-icon {
    color: `).concat(n("inlinemessage.info.color"),`;
}

.p-inlinemessage-success {
    background: `).concat(n("inlinemessage.success.background"),`;
    border: 1px solid `).concat(n("inlinemessage.success.border.color"),`;
    color: `).concat(n("inlinemessage.success.color"),`;
    box-shadow: `).concat(n("inlinemessage.success.shadow"),`;
}

.p-inlinemessage-success .p-inlinemessage-icon {
    color: `).concat(n("inlinemessage.success.color"),`;
}

.p-inlinemessage-warn {
    background: `).concat(n("inlinemessage.warn.background"),`;
    border: 1px solid `).concat(n("inlinemessage.warn.border.color"),`;
    color: `).concat(n("inlinemessage.warn.color"),`;
    box-shadow: `).concat(n("inlinemessage.warn.shadow"),`;
}

.p-inlinemessage-warn .p-inlinemessage-icon {
    color: `).concat(n("inlinemessage.warn.color"),`;
}

.p-inlinemessage-error {
    background: `).concat(n("inlinemessage.error.background"),`;
    border: 1px solid `).concat(n("inlinemessage.error.border.color"),`;
    color: `).concat(n("inlinemessage.error.color"),`;
    box-shadow: `).concat(n("inlinemessage.error.shadow"),`;
}

.p-inlinemessage-error .p-inlinemessage-icon {
    color: `).concat(n("inlinemessage.error.color"),`;
}

.p-inlinemessage-secondary {
    background: `).concat(n("inlinemessage.secondary.background"),`;
    border: 1px solid `).concat(n("inlinemessage.secondary.border.color"),`;
    color: `).concat(n("inlinemessage.secondary.color"),`;
    box-shadow: `).concat(n("inlinemessage.secondary.shadow"),`;
}

.p-inlinemessage-secondary .p-inlinemessage-icon {
    color: `).concat(n("inlinemessage.secondary.color"),`;
}

.p-inlinemessage-contrast {
    background: `).concat(n("inlinemessage.contrast.background"),`;
    border: 1px solid `).concat(n("inlinemessage.contrast.border.color"),`;
    color: `).concat(n("inlinemessage.contrast.color"),`;
    box-shadow: `).concat(n("inlinemessage.contrast.shadow"),`;
}

.p-inlinemessage-contrast .p-inlinemessage-icon {
    color: `).concat(n("inlinemessage.contrast.color"),`;
}
`)},y={root:function(s){var n=s.props,a=s.instance;return["p-inlinemessage p-component p-inlinemessage-"+n.severity,{"p-inlinemessage-icon-only":!a.$slots.default}]},icon:function(s){var n=s.props;return["p-inlinemessage-icon",n.icon]},text:"p-inlinemessage-text"},v=u.extend({name:"inlinemessage",theme:w,classes:y}),x={name:"BaseInlineMessage",extends:p,props:{severity:{type:String,default:"error"},icon:{type:String,default:void 0}},style:v,provide:function(){return{$pcInlineMessage:this,$parentInstance:this}}},k={name:"InlineMessage",extends:x,inheritAttrs:!1,timeout:null,data:function(){return{visible:!0}},mounted:function(){var s=this;this.sticky||setTimeout(function(){s.visible=!1},this.life)},computed:{iconComponent:function(){return{info:d,success:l,warn:g,error:m}[this.severity]}}};function $(e,s,n,a,B,t){return o(),c("div",i({role:"alert","aria-live":"assertive","aria-atomic":"true",class:e.cx("root")},e.ptmi("root")),[r(e.$slots,"icon",{},function(){return[(o(),f(b(e.icon?"span":t.iconComponent),i({class:e.cx("icon")},e.ptm("icon")),null,16,["class"]))]}),e.$slots.default?(o(),c("div",i({key:0,class:e.cx("text")},e.ptm("text")),[r(e.$slots,"default")],16)):h("",!0)],16)}k.render=$;export{k as s};
