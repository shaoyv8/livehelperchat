(window.webpackJsonpLHCReactAPPAdmin=window.webpackJsonpLHCReactAPPAdmin||[]).push([[4],{64:function(e,t,n){"use strict";n.r(t);var c=n(14),i=n.n(c),r=n(2),s=n.n(r),o=n(0),u=n.n(o),l=n(15),d=n.n(l),m=n(66);function f(e,t){var a=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),a.push.apply(a,n)}return a}function h(e){for(var t=1;t<arguments.length;t++){var a=null!=arguments[t]?arguments[t]:{};t%2?f(Object(a),!0).forEach((function(t){s()(e,t,a[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(a)):f(Object(a)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(a,t))}))}return e}function p(e,t){switch(t.type){case"attr":return-1===(n=e.chats.findIndex((function(e){return e.id==t.id})))?e:(e.chats[n]=h({},e.chats[n],{},t.value),e=h({},e));case"attr_remove":return-1===(n=e.chats.findIndex((function(e){return e[t.attr]==t.id})))?e:(e.chats[n]=h({},e.chats[n],{},t.value),e=h({},e));case"update":return h({},e,{},t.value);case"add":return-1===(n=e.chats.findIndex((function(e){return e.id==t.value.id})))?e.chats.unshift(t.value):(e.chats[n].active=!0,e.chats[n].mn=0,e.chats[n].support_chat=!1),h({},e);case"remove":return-1===(n=e.chats.findIndex((function(e){return e.id==t.id})))?e:(e.chats.splice(n,1),h({},e));case"update_chat":return-1===(n=e.chats.findIndex((function(e){return e.id==t.id})))?e:(e.chats[n]=h({},e.chats[n],{},t.value),h({},e));case"msg_received":if(-1===(n=e.chats.findIndex((function(e){return e.id==t.id}))))return e;e.chats[n].msg=t.value.msg;var a=document.getElementById("chat-tab-link-"+t.id);return null!==a&&a.classList.contains("active")?e.chats[n].active=!0:e.chats[n].active=!1,e.chats[n].mn=0==e.chats[n].active?e.chats[n].mn?e.chats[n].mn+t.value.mn:t.value.mn:0,e.chats.splice(0,0,e.chats.splice(n,1)[0]),h({},e);case"refocus":var n;if(-1!==(n=e.chats.findIndex((function(e){return 1==e.active})))){if(t.id==e.chats[n].id)return e;e.chats[n].active=!1}return-1!==(n=e.chats.findIndex((function(e){return e.id==t.id})))&&(e.chats[n].active=!0,e.chats[n].mn=0,e.chats[n].support_chat=!1),h({},e);case"group_offline":return e.group_offline=t.value,h({},e);default:throw new Error("Unknown action!")}}t.default=function(e){var t=Object(o.useReducer)(p,{chats:[],group_offline:!1}),n=i()(t,2),c=n[0],r=n[1],s=Object(o.useRef)(c);Object(o.useEffect)((function(){s.current=c}),[c]);var l=function(e){d.a.get(WWW_DIR_JAVASCRIPT+"front/tabs/(id)/"+(void 0!==e?e.join("/"):function(){var e=[];return c.chats.map((function(t,a){return e.push(t.id)})),e}().join("/"))).then((function(e){e.data.map((function(e,t){var a=document.getElementById("node-js-indicator-"+e.id);null!==a&&(e.live_status="wifi"==a.textContent),r({type:"update_chat",id:e.id,value:e})}))}))};Object(o.useEffect)((function(){function e(e,t){t.focus&&r({type:"attr_remove",id:!0,attr:"active",value:{active:!1}}),r({type:"add",value:{id:e,active:t.focus}}),l([e])}function t(e,t){r({type:"add",value:{id:e,active:!1,mn:1}}),l([e])}function a(e){r({type:"remove",id:e})}function n(e){r({type:"refocus",id:e})}function c(e){r({type:"group_offline",value:lhinst.hidenicknamesstatus}),Object.keys(e.result_status).map((function(t){r({type:"update_chat",id:e.result_status[t].chat_id,value:e.result_status[t]})})),"false"!==e.result&&Object.keys(e.result).map((function(t){r({type:"msg_received",id:e.result[t].chat_id,value:{msg:e.result[t].msg,mn:e.result[t].mn}})}))}function i(e){r({type:"update_chat",id:e.id,value:{tp:"true",tx:e.txt}})}function o(e){r({type:"update_chat",id:e.id,value:{tp:"false"}})}function u(e){r({type:"update_chat",id:e.id,value:{live_status:e.status}})}function d(e,t){var a=s.current.chats.findIndex((function(e){return 1==e.active}));-1!==a&&(s.current.chats.length-1>a&&0==t?f(s.current.chats[a+1]):a>0&&1==t&&f(s.current.chats[a-1]))}if(ee.addListener("chatStartTab",e),ee.addListener("chatStartBackground",t),ee.addListener("removeSynchroChat",a),ee.addListener("chatTabClicked",n),ee.addListener("chatTabFocused",n),ee.addListener("chatAdminSync",c),ee.addListener("supportUnreadChat",(function(e){e.id&&1==e.unread&&r({type:"update_chat",id:e.id,value:{support_chat:!0}})})),ee.addListener("nodeJsTypingVisitor",i),ee.addListener("nodeJsTypingVisitorStopped",o),ee.addListener("nodeJsVisitorStatus",u),ee.addListener("activateNextTab",d),localStorage){var m=localStorage.getItem("achat_id");if(null!==m&&""!==m){var h=m.split(","),p=[];h.forEach((function(e){var t=document.getElementById("chat-tab-link-"+e);if(null!==t){var a=t.classList.contains("active");p.push({id:parseInt(e),active:a})}})),r({type:"update",value:{chats:p}}),h.length>0&&l(h),setTimeout((function(){h.forEach((function(e){var t=document.getElementById("chat-tab-link-"+e);null!==t&&t.classList.contains("active")&&n(parseInt(e))}))}),1e3)}}return function(){ee.removeListener("chatStartTab",e),ee.removeListener("chatStartBackground",t),ee.removeListener("removeSynchroChat",a),ee.removeListener("chatTabClicked",n),ee.removeListener("chatTabFocused",n),ee.removeListener("chatAdminSync",c),ee.removeListener("supportUnreadChat",c),ee.removeListener("nodeJsTypingVisitor",i),ee.removeListener("nodeJsTypingVisitorStopped",o),ee.removeListener("nodeJsVisitorStatus",u),ee.removeListener("activateNextTab",d)}}),[]);var f=function(e){$("#chat-tab-link-"+e.id).click()},h=Object(m.a)("chat_tabs"),v=h.t;h.i18n;return u.a.createElement(u.a.Fragment,null,(!c.chats||0==c.chats.length)&&u.a.createElement("div",{className:"text-center text-muted p-2"},u.a.createElement("span",{className:"material-icons"},"chat"),v("chat_tabs.open_chats")),c.chats.map((function(e,t){return u.a.createElement("div",{title:e.id,onClick:function(){return f(e)},className:"p-1 action-image chat-tabs-row"+(e.active?" chat-tab-active":"")},u.a.createElement("div",{className:"fs12"},u.a.createElement("renderAlerts",{datalist:e.aicons}),e.aicons&&Object.entries(a).map((function(e){var t=i()(e,2);t[0],t[1]})),e.support_chat&&u.a.createElement("span",{className:"whatshot blink-ani text-warning material-icons"},"whatshot"),u.a.createElement("i",{className:"material-icons "+("boolean"==typeof e.live_status?!0===e.live_status?"icon-user-online":"icon-user-offline":2==e.us?"icon-user-away":0==e.us?"icon-user-online":"icon-user-offline")},"face"),u.a.createElement("i",{className:"material-icons icon-user-online "+(1==e.um?"icon-user-offline":"icon-user-online")},"send"),e.cc&&u.a.createElement("img",{title:e.cn,src:e.cc,alt:""})," ",(0==c.group_offline||!(0!=e.us))&&u.a.createElement("span",{className:(e.mn>0?"font-weight-bold ":"")+(0==e.cs?"text-warning":"")},e.nick||e.id),e.mn>0&&u.a.createElement("span",{className:"msg-nm pl-1"},"(",e.mn,")"),e.lmsg&&u.a.createElement("span",{className:"text-muted"}," ",e.lmsg),u.a.createElement("button",{type:"button",onClick:function(t){return function(e,t){e.preventDefault(),e.stopPropagation(),lhinst.removeDialogTab(t.id,$("#tabs"),!0)}(t,e)},className:"float-right btn-link m-0 ml-1 p-0 btn btn-xs"},u.a.createElement("i",{className:"material-icons mr-0"},"close")),e.dep&&u.a.createElement("span",{className:"float-right text-muted text-truncate mw-80px"},u.a.createElement("span",{className:"material-icons"},"home"),e.dep),e.co==confLH.user_id&&u.a.createElement("span",{className:"float-right text-muted"},u.a.createElement("span",{title:v("chat_tabs.chat_owner"),className:"material-icons"},"account_box"))),(e.msg||"true"==e.tp&&e.tx)&&u.a.createElement("div",{className:"fs13 text-muted pt-1"},u.a.createElement("span",{title:"true"==e.tp&&e.tx?e.tx:e.msg,className:"d-inline-block text-truncate mw-100 "+(e.mn>0?"font-weight-bold":"")+("true"==e.tp&&e.tx?" font-italic":"")},"true"==e.tp&&e.tx?e.tx:e.msg)))})))}}}]);
//# sourceMappingURL=4.6a8a56659ac231da3010.js.map