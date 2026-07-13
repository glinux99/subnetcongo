import{I as p,b4 as k,am as x,K as l,e as r,f,d as s,J as i,p as m,c as h,l as y,n as v}from"./app-PGKUwesN.js";import{s as g}from"./index-DadBuwCL.js";import{s as C}from"./index-lK6Dte1I.js";import{s as S}from"./index-DkOGVXLW.js";var B=function(c){var e=c.dt;return`
.p-checkbox {
    position: relative;
    display: inline-flex;
    user-select: none;
    vertical-align: bottom;
    width: `.concat(e("checkbox.width"),`;
    height: `).concat(e("checkbox.height"),`;
}

.p-checkbox-input {
    cursor: pointer;
    appearance: none;
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    padding: 0;
    margin: 0;
    opacity: 0;
    z-index: 1;
    outline: 0 none;
    border: 1px solid transparent;
    border-radius: `).concat(e("checkbox.border.radius"),`;
}

.p-checkbox-box {
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: `).concat(e("checkbox.border.radius"),`;
    border: 1px solid `).concat(e("checkbox.border.color"),`;
    background: `).concat(e("checkbox.background"),`;
    width: `).concat(e("checkbox.width"),`;
    height: `).concat(e("checkbox.height"),`;
    transition: background `).concat(e("checkbox.transition.duration"),", color ").concat(e("checkbox.transition.duration"),", border-color ").concat(e("checkbox.transition.duration"),", box-shadow ").concat(e("checkbox.transition.duration"),", outline-color ").concat(e("checkbox.transition.duration"),`;
    outline-color: transparent;
    box-shadow: `).concat(e("checkbox.shadow"),`;
}

.p-checkbox-icon {
    transition-duration: `).concat(e("checkbox.transition.duration"),`;
    color: `).concat(e("checkbox.icon.color"),`;
    font-size: `).concat(e("checkbox.icon.size"),`;
    width: `).concat(e("checkbox.icon.size"),`;
    height: `).concat(e("checkbox.icon.size"),`;
}

.p-checkbox:not(.p-disabled):has(.p-checkbox-input:hover) .p-checkbox-box {
    border-color: `).concat(e("checkbox.hover.border.color"),`;
}

.p-checkbox-checked .p-checkbox-box {
    border-color: `).concat(e("checkbox.checked.border.color"),`;
    background: `).concat(e("checkbox.checked.background"),`;
}

.p-checkbox-checked .p-checkbox-icon {
    color: `).concat(e("checkbox.icon.checked.color"),`;
}

.p-checkbox-checked:not(.p-disabled):has(.p-checkbox-input:hover) .p-checkbox-box {
    background: `).concat(e("checkbox.checked.hover.background"),`;
    border-color: `).concat(e("checkbox.checked.hover.border.color"),`;
}

.p-checkbox-checked:not(.p-disabled):has(.p-checkbox-input:hover) .p-checkbox-icon {
    color: `).concat(e("checkbox.icon.checked.hover.color"),`;
}

.p-checkbox:not(.p-disabled):has(.p-checkbox-input:focus-visible) .p-checkbox-box {
    border-color: `).concat(e("checkbox.focus.border.color"),`;
    box-shadow: `).concat(e("checkbox.focus.ring.shadow"),`;
    outline: `).concat(e("checkbox.focus.ring.width")," ").concat(e("checkbox.focus.ring.style")," ").concat(e("checkbox.focus.ring.color"),`;
    outline-offset: `).concat(e("checkbox.focus.ring.offset"),`;
}

.p-checkbox-checked:not(.p-disabled):has(.p-checkbox-input:focus-visible) .p-checkbox-box {
    border-color: `).concat(e("checkbox.checked.focus.border.color"),`;
}

.p-checkbox.p-invalid > .p-checkbox-box {
    border-color: `).concat(e("checkbox.invalid.border.color"),`;
}

.p-checkbox.p-variant-filled .p-checkbox-box {
    background: `).concat(e("checkbox.filled.background"),`;
}

.p-checkbox-checked.p-variant-filled .p-checkbox-box {
    background: `).concat(e("checkbox.checked.background"),`;
}

.p-checkbox-checked.p-variant-filled:not(.p-disabled):has(.p-checkbox-input:hover) .p-checkbox-box {
    background: `).concat(e("checkbox.checked.hover.background"),`;
}

.p-checkbox.p-disabled {
    opacity: 1;
}

.p-checkbox.p-disabled .p-checkbox-box {
    background: `).concat(e("checkbox.disabled.background"),`;
    border-color: `).concat(e("checkbox.checked.disabled.border.color"),`;
}

.p-checkbox.p-disabled .p-checkbox-box .p-checkbox-icon {
    color: `).concat(e("checkbox.icon.disabled.color"),`;
}
`)},I={root:function(c){var e=c.instance,t=c.props;return["p-checkbox p-component",{"p-checkbox-checked":e.checked,"p-disabled":t.disabled,"p-invalid":t.invalid,"p-variant-filled":t.variant?t.variant==="filled":e.$primevue.config.inputStyle==="filled"||e.$primevue.config.inputVariant==="filled"}]},box:"p-checkbox-box",input:"p-checkbox-input",icon:"p-checkbox-icon"},V=p.extend({name:"checkbox",theme:B,classes:I}),w={name:"BaseCheckbox",extends:S,props:{value:null,modelValue:null,binary:Boolean,name:{type:String,default:null},indeterminate:{type:Boolean,default:!1},trueValue:{type:null,default:!0},falseValue:{type:null,default:!1},variant:{type:String,default:null},invalid:{type:Boolean,default:!1},disabled:{type:Boolean,default:!1},readonly:{type:Boolean,default:!1},required:{type:Boolean,default:!1},tabindex:{type:Number,default:null},inputId:{type:String,default:null},inputClass:{type:[String,Object],default:null},inputStyle:{type:Object,default:null},ariaLabelledby:{type:String,default:null},ariaLabel:{type:String,default:null}},style:V,provide:function(){return{$pcCheckbox:this,$parentInstance:this}}};function A(n){return z(n)||P(n)||O(n)||T()}function T(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function O(n,c){if(n){if(typeof n=="string")return d(n,c);var e={}.toString.call(n).slice(8,-1);return e==="Object"&&n.constructor&&(e=n.constructor.name),e==="Map"||e==="Set"?Array.from(n):e==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(e)?d(n,c):void 0}}function P(n){if(typeof Symbol<"u"&&n[Symbol.iterator]!=null||n["@@iterator"]!=null)return Array.from(n)}function z(n){if(Array.isArray(n))return d(n)}function d(n,c){(c==null||c>n.length)&&(c=n.length);for(var e=0,t=Array(c);e<c;e++)t[e]=n[e];return t}var j={name:"Checkbox",extends:w,inheritAttrs:!1,emits:["update:modelValue","change","focus","blur","update:indeterminate"],data:function(){return{d_indeterminate:this.indeterminate}},watch:{indeterminate:function(c){this.d_indeterminate=c}},methods:{getPTOptions:function(c){var e=c==="root"?this.ptmi:this.ptm;return e(c,{context:{checked:this.checked,indeterminate:this.d_indeterminate,disabled:this.disabled}})},onChange:function(c){var e=this;if(!this.disabled&&!this.readonly){var t;this.binary?t=this.d_indeterminate?this.trueValue:this.checked?this.falseValue:this.trueValue:this.checked||this.d_indeterminate?t=this.modelValue.filter(function(a){return!x(a,e.value)}):t=this.modelValue?[].concat(A(this.modelValue),[this.value]):[this.value],this.d_indeterminate&&(this.d_indeterminate=!1,this.$emit("update:indeterminate",this.d_indeterminate)),this.$emit("update:modelValue",t),this.$emit("change",c)}},onFocus:function(c){this.$emit("focus",c)},onBlur:function(c){this.$emit("blur",c)}},computed:{checked:function(){return this.d_indeterminate?!1:this.binary?this.modelValue===this.trueValue:k(this.value,this.modelValue)}},components:{CheckIcon:g,MinusIcon:C}},q=["data-p-checked","data-p-indeterminate","data-p-disabled"],F=["id","value","name","checked","tabindex","disabled","readonly","required","aria-labelledby","aria-label","aria-invalid","aria-checked"];function L(n,c,e,t,a,o){var b=l("CheckIcon"),u=l("MinusIcon");return r(),f("div",i({class:n.cx("root")},o.getPTOptions("root"),{"data-p-checked":o.checked,"data-p-indeterminate":a.d_indeterminate||void 0,"data-p-disabled":n.disabled}),[s("input",i({id:n.inputId,type:"checkbox",class:[n.cx("input"),n.inputClass],style:n.inputStyle,value:n.value,name:n.name,checked:o.checked,tabindex:n.tabindex,disabled:n.disabled,readonly:n.readonly,required:n.required,"aria-labelledby":n.ariaLabelledby,"aria-label":n.ariaLabel,"aria-invalid":n.invalid||void 0,"aria-checked":a.d_indeterminate?"mixed":void 0,onFocus:c[0]||(c[0]=function(){return o.onFocus&&o.onFocus.apply(o,arguments)}),onBlur:c[1]||(c[1]=function(){return o.onBlur&&o.onBlur.apply(o,arguments)}),onChange:c[2]||(c[2]=function(){return o.onChange&&o.onChange.apply(o,arguments)})},o.getPTOptions("input")),null,16,F),s("div",i({class:n.cx("box")},o.getPTOptions("box")),[m(n.$slots,"icon",{checked:o.checked,indeterminate:a.d_indeterminate,class:v(n.cx("icon"))},function(){return[o.checked?(r(),h(b,i({key:0,class:n.cx("icon")},o.getPTOptions("icon")),null,16,["class"])):a.d_indeterminate?(r(),h(u,i({key:1,class:n.cx("icon")},o.getPTOptions("icon")),null,16,["class"])):y("",!0)]})],16)],16,q)}j.render=L;export{j as s};
