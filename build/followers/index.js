(()=>{var e={20:(e,t,a)=>{"use strict";var r=a(609),n=Symbol.for("react.element"),l=(Symbol.for("react.fragment"),Object.prototype.hasOwnProperty),o=r.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner,i={key:!0,ref:!0,__self:!0,__source:!0};t.jsx=function(e,t,a){var r,c={},s=null,p=null;for(r in void 0!==a&&(s=""+a),void 0!==t.key&&(s=""+t.key),void 0!==t.ref&&(p=t.ref),t)l.call(t,r)&&!i.hasOwnProperty(r)&&(c[r]=t[r]);if(e&&e.defaultProps)for(r in t=e.defaultProps)void 0===c[r]&&(c[r]=t[r]);return{$$typeof:n,type:e,key:s,ref:p,props:c,_owner:o.current}}},848:(e,t,a)=>{"use strict";e.exports=a(20)},609:e=>{"use strict";e.exports=window.React},942:(e,t)=>{var a;!function(){"use strict";var r={}.hasOwnProperty;function n(){for(var e="",t=0;t<arguments.length;t++){var a=arguments[t];a&&(e=o(e,l(a)))}return e}function l(e){if("string"==typeof e||"number"==typeof e)return e;if("object"!=typeof e)return"";if(Array.isArray(e))return n.apply(null,e);if(e.toString!==Object.prototype.toString&&!e.toString.toString().includes("[native code]"))return e.toString();var t="";for(var a in e)r.call(e,a)&&e[a]&&(t=o(t,a));return t}function o(e,t){return t?e?e+" "+t:e+t:e}e.exports?(n.default=n,e.exports=n):void 0===(a=function(){return n}.apply(t,[]))||(e.exports=a)}()}},t={};function a(r){var n=t[r];if(void 0!==n)return n.exports;var l=t[r]={exports:{}};return e[r](l,l.exports,a),l.exports}a.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return a.d(t,{a:t}),t},a.d=(e,t)=>{for(var r in t)a.o(t,r)&&!a.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},a.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{"use strict";const e=window.wp.blocks,t=window.wp.primitives;var r=a(848);const n=(0,r.jsx)(t.SVG,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24",children:(0,r.jsx)(t.Path,{d:"M15.5 9.5a1 1 0 100-2 1 1 0 000 2zm0 1.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5zm-2.25 6v-2a2.75 2.75 0 00-2.75-2.75h-4A2.75 2.75 0 003.75 15v2h1.5v-2c0-.69.56-1.25 1.25-1.25h4c.69 0 1.25.56 1.25 1.25v2h1.5zm7-2v2h-1.5v-2c0-.69-.56-1.25-1.25-1.25H15v-1.5h2.5A2.75 2.75 0 0120.25 15zM9.5 8.5a1 1 0 11-2 0 1 1 0 012 0zm1.5 0a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z",fillRule:"evenodd"})});var l=a(609);const o=window.wp.components,i=window.wp.element,c=window.wp.blockEditor,s=window.wp.data,p=window.wp.coreData,u=window.wp.i18n,m=window.wp.apiFetch;var v=a.n(m);const d=window.wp.url;var w=a(942),b=a.n(w);function f({active:e,children:t,page:a,pageClick:r,className:n}){const o=b()("wp-block activitypub-pager",n,{current:e});return(0,l.createElement)("a",{className:o,onClick:t=>{t.preventDefault(),!e&&r(a)}},t)}function y({compact:e,nextLabel:t,page:a,pageClick:r,perPage:n,prevLabel:o,total:i,variant:c="outlined"}){const s=((e,t)=>{let a=[1,e-2,e-1,e,e+1,e+2,t];a.sort(((e,t)=>e-t)),a=a.filter(((e,a,r)=>e>=1&&e<=t&&r.lastIndexOf(e)===a));for(let e=a.length-2;e>=0;e--)a[e]===a[e+1]&&a.splice(e+1,1);return a})(a,Math.ceil(i/n)),p=b()("alignwide wp-block-query-pagination is-content-justification-space-between is-layout-flex wp-block-query-pagination-is-layout-flex",`is-${c}`,{"is-compact":e});return(0,l.createElement)("nav",{className:p},o&&(0,l.createElement)(f,{key:"prev",page:a-1,pageClick:r,active:1===a,"aria-label":o,className:"wp-block-query-pagination-previous block-editor-block-list__block"},o),!e&&(0,l.createElement)("div",{className:"block-editor-block-list__block wp-block wp-block-query-pagination-numbers"},s.map((e=>(0,l.createElement)(f,{key:e,page:e,pageClick:r,active:e===a,className:"page-numbers"},e)))),t&&(0,l.createElement)(f,{key:"next",page:a+1,pageClick:r,active:a===Math.ceil(i/n),"aria-label":t,className:"wp-block-query-pagination-next block-editor-block-list__block"},t))}function g(){return window._activityPubOptions||{}}function h({selectedUser:e,per_page:t,order:a,title:r,page:n,setPage:o,className:c="",followLinks:s=!0,followerData:p=!1}){const m="site"===e?0:e,[w,b]=(0,l.useState)([]),[f,h]=(0,l.useState)(0),[k,E]=(0,l.useState)(0),[x,S]=function(){const[e,t]=(0,l.useState)(1);return[e,t]}(),N=n||x,C=o||S,O=(0,i.createInterpolateElement)(/* translators: arrow for previous followers link */ /* translators: arrow for previous followers link */
(0,u.__)("<span>←</span> Less","activitypub"),{span:(0,l.createElement)("span",{className:"wp-block-query-pagination-previous-arrow is-arrow-arrow","aria-hidden":"true"})}),P=(0,i.createInterpolateElement)(/* translators: arrow for next followers link */ /* translators: arrow for next followers link */
(0,u.__)("More <span>→</span>","activitypub"),{span:(0,l.createElement)("span",{className:"wp-block-query-pagination-next-arrow is-arrow-arrow","aria-hidden":"true"})}),I=(e,a)=>{b(e),E(a),h(Math.ceil(a/t))};return(0,l.useEffect)((()=>{if(p&&1===N)return I(p.followers,p.total);const e=function(e,t,a,r){const{namespace:n}=g(),l=`/${n}/actors/${e}/followers`,o={per_page:t,order:a,page:r,context:"full"};return(0,d.addQueryArgs)(l,o)}(m,t,a,N);v()({path:e}).then((e=>I(e.orderedItems,e.totalItems))).catch((()=>{}))}),[m,t,a,N,p]),(0,l.createElement)("div",{className:"activitypub-follower-block "+c},(0,l.createElement)("h3",null,r),(0,l.createElement)("ul",null,w&&w.map((e=>(0,l.createElement)("li",{key:e.url},(0,l.createElement)(_,{...e,followLinks:s}))))),f>1&&(0,l.createElement)(y,{page:N,perPage:t,total:k,pageClick:C,nextLabel:P,prevLabel:O,compact:"is-style-compact"===c}))}function _({name:e,icon:t,url:a,preferredUsername:r,followLinks:n=!0}){const i=`@${r}`,c={};return n||(c.onClick=e=>e.preventDefault()),(0,l.createElement)(o.ExternalLink,{className:"activitypub-link",href:a,title:i,...c},(0,l.createElement)("img",{width:"40",height:"40",src:t.url,className:"avatar activitypub-avatar",alt:e}),(0,l.createElement)("span",{className:"activitypub-actor"},(0,l.createElement)("strong",{className:"activitypub-name"},e),(0,l.createElement)("span",{className:"sep"},"/"),(0,l.createElement)("span",{className:"activitypub-handle"},i)))}function k({name:e}){const{enabled:t}=g(),a=t?.site?"":(0,u.__)("It will be empty in other non-author contexts.","activitypub"),r=(0,u.sprintf)(/* translators: %1$s: block name, %2$s: extra information for non-author context */ /* translators: %1$s: block name, %2$s: extra information for non-author context */
(0,u.__)("This <strong>%1$s</strong> block will adapt to the page it is on, displaying the user profile associated with a post author (in a loop) or a user archive. %2$s","activitypub"),e,a).trim();return(0,l.createElement)(o.Card,null,(0,l.createElement)(o.CardBody,null,(0,i.createInterpolateElement)(r,{strong:(0,l.createElement)("strong",null)})))}(0,e.registerBlockType)("activitypub/followers",{edit:function({attributes:e,setAttributes:t,context:{postType:a,postId:r}}){const{order:n,per_page:m,selectedUser:v,title:d}=e,w=(0,c.useBlockProps)(),[b,f]=(0,i.useState)(1),y=[{label:(0,u.__)("New to old","activitypub"),value:"desc"},{label:(0,u.__)("Old to new","activitypub"),value:"asc"}],_=function({withInherit:e=!1}){const{enabled:t}=g(),a=t?.users?(0,s.useSelect)((e=>e("core").getUsers({who:"authors"}))):[];return(0,i.useMemo)((()=>{if(!a)return[];const r=[];return t?.site&&r.push({label:(0,u.__)("Site","activitypub"),value:"site"}),e&&t?.users&&r.push({label:(0,u.__)("Dynamic User","activitypub"),value:"inherit"}),a.reduce(((e,t)=>(e.push({label:t.name,value:`${t.id}`}),e)),r)}),[a])}({withInherit:!0}),E=e=>a=>{f(1),t({[e]:a})},x=(0,s.useSelect)((e=>{const{getEditedEntityRecord:t}=e(p.store),n=t("postType",a,r)?.author;return null!=n?n:null}),[a,r]);return(0,i.useEffect)((()=>{_.length&&(_.find((({value:e})=>e===v))||t({selectedUser:_[0].value}))}),[v,_]),(0,l.createElement)("div",{...w},(0,l.createElement)(c.InspectorControls,{key:"setting"},(0,l.createElement)(o.PanelBody,{title:(0,u.__)("Followers Options","activitypub")},(0,l.createElement)(o.TextControl,{label:(0,u.__)("Title","activitypub"),help:(0,u.__)("Title to display above the list of followers. Blank for none.","activitypub"),value:d,onChange:e=>t({title:e})}),_.length>1&&(0,l.createElement)(o.SelectControl,{label:(0,u.__)("Select User","activitypub"),value:v,options:_,onChange:E("selectedUser")}),(0,l.createElement)(o.SelectControl,{label:(0,u.__)("Sort","activitypub"),value:n,options:y,onChange:E("order")}),(0,l.createElement)(o.RangeControl,{label:(0,u.__)("Number of Followers","activitypub"),value:m,onChange:E("per_page"),min:1,max:10}))),"inherit"===v?x?(0,l.createElement)(h,{...e,page:b,setPage:f,followLinks:!1,selectedUser:x}):(0,l.createElement)(k,{name:(0,u.__)("Followers","activitypub")}):(0,l.createElement)(h,{...e,page:b,setPage:f,followLinks:!1}))},save:()=>null,icon:n})})()})();