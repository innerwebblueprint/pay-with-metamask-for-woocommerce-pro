"use strict";(globalThis.webpackChunkpay_with_metamask=globalThis.webpackChunkpay_with_metamask||[]).push([[316],{1316:(e,a,t)=>{t.d(a,{offchainLookup:()=>g,offchainLookupSignature:()=>m});var r=t(5176),s=t(8463),n=t(6329),o=t(1526);class c extends n.C{constructor({callbackSelector:e,cause:a,data:t,extraData:r,sender:s,urls:n}){super(a.shortMessage||"An error occurred while fetching for an offchain result.",{cause:a,metaMessages:[...a.metaMessages||[],a.metaMessages?.length?"":[],"Offchain Gateway Call:",n&&["  Gateway URL(s):",...n.map((e=>`    ${(0,o.ID)(e)}`))],`  Sender: ${s}`,`  Data: ${t}`,`  Callback selector: ${e}`,`  Extra data: ${r}`].flat()}),Object.defineProperty(this,"name",{enumerable:!0,configurable:!0,writable:!0,value:"OffchainLookupError"})}}class d extends n.C{constructor({result:e,url:a}){super("Offchain gateway response is malformed. Response data must be a hex value.",{metaMessages:[`Gateway URL: ${(0,o.ID)(a)}`,`Response: ${(0,s.A)(e)}`]}),Object.defineProperty(this,"name",{enumerable:!0,configurable:!0,writable:!0,value:"OffchainLookupResponseMalformedError"})}}class i extends n.C{constructor({sender:e,to:a}){super("Reverted sender address does not match target contract address (`to`).",{metaMessages:[`Contract address: ${a}`,`OffchainLookup sender address: ${e}`]}),Object.defineProperty(this,"name",{enumerable:!0,configurable:!0,writable:!0,value:"OffchainLookupSenderMismatchError"})}}var l=t(6595),u=t(5462),f=t(4531),h=t(4306),p=t(9873),b=t(5419),w=t(6394);const m="0x556f1830",y={name:"OffchainLookup",type:"error",inputs:[{name:"sender",type:"address"},{name:"urls",type:"string[]"},{name:"callData",type:"bytes"},{name:"callbackFunction",type:"bytes4"},{name:"extraData",type:"bytes"}]};async function g(e,{blockNumber:a,blockTag:t,data:s,to:n}){const{args:o}=(0,u.W)({data:s,abi:[y]}),[d,l,w,m,g]=o,{ccipRead:C}=e,O=C&&"function"==typeof C?.request?C.request:k;try{if(!function(e,a){if(!(0,p.P)(e,{strict:!1}))throw new h.M({address:e});if(!(0,p.P)(a,{strict:!1}))throw new h.M({address:a});return e.toLowerCase()===a.toLowerCase()}(n,d))throw new i({sender:d,to:n});const s=await O({data:w,sender:d,urls:l}),{data:o}=await(0,r.T)(e,{blockNumber:a,blockTag:t,data:(0,b.xW)([m,(0,f.h)([{type:"bytes"},{type:"bytes"}],[s,g])]),to:n});return o}catch(e){throw new c({callbackSelector:m,cause:e,data:s,extraData:g,sender:d,urls:l})}}async function k({data:e,sender:a,urls:t}){let r=new Error("An unknown error occurred.");for(let n=0;n<t.length;n++){const o=t[n],c=o.includes("{data}")?"GET":"POST",i="POST"===c?{data:e,sender:a}:void 0;try{const t=await fetch(o.replace("{sender}",a).replace("{data}",e),{body:JSON.stringify(i),method:c});let n;if(n=t.headers.get("Content-Type")?.startsWith("application/json")?(await t.json()).data:await t.text(),!t.ok){r=new l.Ci({body:i,details:n?.error?(0,s.A)(n.error):t.statusText,headers:t.headers,status:t.status,url:o});continue}if(!(0,w.q)(n)){r=new d({result:n,url:o});continue}return n}catch(e){r=new l.Ci({body:i,details:e.message,url:o})}}throw r}}}]);