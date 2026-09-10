import{K as s,az as v,o as m,c as p,F as d,e as h,M as l,b as a,z as c,h as u}from"./app-CMHddLn0.js";import{s as f}from"./index-CXZ3jvIo.js";var g=function(i){var e=i.dt;return`
.p-timeline {
    display: flex;
    flex-grow: 1;
    flex-direction: column;
}

.p-timeline-left .p-timeline-event-opposite {
    text-align: right;
}

.p-timeline-left .p-timeline-event-content {
    text-align: left;
}

.p-timeline-right .p-timeline-event {
    flex-direction: row-reverse;
}

.p-timeline-right .p-timeline-event-opposite {
    text-align: left;
}

.p-timeline-right .p-timeline-event-content {
    text-align: right;
}

.p-timeline-vertical.p-timeline-alternate .p-timeline-event:nth-child(even) {
    flex-direction: row-reverse;
}

.p-timeline-vertical.p-timeline-alternate .p-timeline-event:nth-child(odd) .p-timeline-event-opposite {
    text-align: right;
}

.p-timeline-vertical.p-timeline-alternate .p-timeline-event:nth-child(odd) .p-timeline-event-content {
    text-align: left;
}

.p-timeline-vertical.p-timeline-alternate .p-timeline-event:nth-child(even) .p-timeline-event-opposite {
    text-align: left;
}

.p-timeline-vertical.p-timeline-alternate .p-timeline-event:nth-child(even) .p-timeline-event-content {
    text-align: right;
}

.p-timeline-vertical .p-timeline-event-opposite,
.p-timeline-vertical .p-timeline-event-content {
    padding: `.concat(e("timeline.vertical.event.content.padding"),`;
}

.p-timeline-vertical .p-timeline-event-connector {
    width: `).concat(e("timeline.event.connector.size"),`;
}

.p-timeline-event {
    display: flex;
    position: relative;
    min-height: `).concat(e("timeline.event.min.height"),`;
}

.p-timeline-event:last-child {
    min-height: 0;
}

.p-timeline-event-opposite {
    flex: 1;
}

.p-timeline-event-content {
    flex: 1;
}

.p-timeline-event-separator {
    flex: 0;
    display: flex;
    align-items: center;
    flex-direction: column;
}

.p-timeline-event-marker {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    position: relative;
    align-self: baseline;
    border-width: `).concat(e("timeline.event.marker.border.width"),`;
    border-style: solid;
    border-color: `).concat(e("timeline.event.marker.border.color"),`;
    border-radius: `).concat(e("timeline.event.marker.border.radius"),`;
    width: `).concat(e("timeline.event.marker.size"),`;
    height: `).concat(e("timeline.event.marker.size"),`;
    background: `).concat(e("timeline.event.marker.background"),`;
}

.p-timeline-event-marker::before {
    content: " ";
    border-radius: `).concat(e("timeline.event.marker.content.border.radius"),`;
    width: `).concat(e("timeline.event.marker.content.size"),`;
    height:`).concat(e("timeline.event.marker.content.size"),`;
    background: `).concat(e("timeline.event.marker.content.background"),`;
}

.p-timeline-event-marker::after {
    content: " ";
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: `).concat(e("timeline.event.marker.border.radius"),`;
    box-shadow: `).concat(e("timeline.event.marker.content.inset.shadow"),`;
}

.p-timeline-event-connector {
    flex-grow: 1;
    background: `).concat(e("timeline.event.connector.color"),`;
}

.p-timeline-horizontal {
    flex-direction: row;
}

.p-timeline-horizontal .p-timeline-event {
    flex-direction: column;
    flex: 1;
}

.p-timeline-horizontal .p-timeline-event:last-child {
    flex: 0;
}

.p-timeline-horizontal .p-timeline-event-separator {
    flex-direction: row;
}

.p-timeline-horizontal .p-timeline-event-connector {
    width: 100%;
    height: `).concat(e("timeline.event.connector.size"),`;
}

.p-timeline-horizontal .p-timeline-event-opposite,
.p-timeline-horizontal .p-timeline-event-content {
    padding: `).concat(e("timeline.horizontal.event.content.padding"),`;
}

.p-timeline-horizontal.p-timeline-alternate .p-timeline-event:nth-child(even) {
    flex-direction: column-reverse;
}

.p-timeline-bottom .p-timeline-event {
    flex-direction: column-reverse;
}
`)},k={root:function(i){var e=i.props;return["p-timeline p-component","p-timeline-"+e.align,"p-timeline-"+e.layout]},event:"p-timeline-event",eventOpposite:"p-timeline-event-opposite",eventSeparator:"p-timeline-event-separator",eventMarker:"p-timeline-event-marker",eventConnector:"p-timeline-event-connector",eventContent:"p-timeline-event-content"},b=s.extend({name:"timeline",theme:g,classes:k}),x={name:"BaseTimeline",extends:f,props:{value:null,align:{mode:String,default:"left"},layout:{mode:String,default:"vertical"},dataKey:null},style:b,provide:function(){return{$pcTimeline:this,$parentInstance:this}}},y={name:"Timeline",extends:x,inheritAttrs:!1,methods:{getKey:function(i,e){return this.dataKey?v(i,this.dataKey):e},getPTOptions:function(i,e){return this.ptm(i,{context:{index:e,count:this.value.length}})}}};function z(n,i,e,w,T,r){return m(),p("div",l({class:n.cx("root")},n.ptmi("root")),[(m(!0),p(d,null,h(n.value,function(o,t){return m(),p("div",l({key:r.getKey(o,t),class:n.cx("event"),ref_for:!0},r.getPTOptions("event",t)),[a("div",l({class:n.cx("eventOpposite",{index:t}),ref_for:!0},r.getPTOptions("eventOpposite",t)),[c(n.$slots,"opposite",{item:o,index:t})],16),a("div",l({class:n.cx("eventSeparator"),ref_for:!0},r.getPTOptions("eventSeparator",t)),[c(n.$slots,"marker",{item:o,index:t},function(){return[a("div",l({class:n.cx("eventMarker"),ref_for:!0},r.getPTOptions("eventMarker",t)),null,16)]}),t!==n.value.length-1?c(n.$slots,"connector",{key:0,item:o,index:t},function(){return[a("div",l({class:n.cx("eventConnector"),ref_for:!0},r.getPTOptions("eventConnector",t)),null,16)]}):u("",!0)],16),a("div",l({class:n.cx("eventContent"),ref_for:!0},r.getPTOptions("eventContent",t)),[c(n.$slots,"content",{item:o,index:t})],16)],16)}),128))],16)}y.render=z;export{y as s};
