(()=>{
'use strict';
const qs=(s,r=document)=>r.querySelector(s),qsa=(s,r=document)=>[...r.querySelectorAll(s)];
const app=qs('[data-portal-app]');if(!app)return;
let navigating=false;
const loader=()=>qs('#admin-loader');
const setLoading=on=>{const el=loader();if(!el)return;el.classList.toggle('show',!!on);el.setAttribute('aria-hidden',on?'false':'true');};
const samePortal=url=>url.origin===location.origin&&/\/portal(?:\/|$)/.test(url.pathname);
function syncQr(){
  qsa('[data-qr-code]').forEach(el=>{if(el.dataset.qrReady||!window.QRCode)return;const text=el.dataset.qrText||'';if(!text)return;el.dataset.qrReady='1';el.innerHTML='';const compact=el.hasAttribute('data-id-qr');new QRCode(el,{text,width:compact?108:150,height:compact?108:150,colorDark:'#061817',colorLight:'#ffffff',correctLevel:QRCode.CorrectLevel.H});});
}
function bindIdActions(){
  const card=qs('[data-member-id-card]');if(!card)return;
  qsa('[data-print-id]').forEach(btn=>{if(btn.dataset.bound)return;btn.dataset.bound='1';btn.addEventListener('click',()=>window.print());});
  qsa('[data-download-id]').forEach(download=>{if(download.dataset.bound)return;download.dataset.bound='1';download.addEventListener('click',()=>downloadId(card,download));});
}
const xml=value=>String(value??'').replace(/[<>&'\"]/g,ch=>({'<':'&lt;','>':'&gt;','&':'&amp;',"'":'&apos;','"':'&quot;'}[ch]));
async function dataUrl(src){if(!src)return '';try{const r=await fetch(src,{credentials:'same-origin'});if(!r.ok)throw new Error();const b=await r.blob();return await new Promise((resolve,reject)=>{const fr=new FileReader();fr.onload=()=>resolve(fr.result);fr.onerror=reject;fr.readAsDataURL(b);});}catch{return '';}}
async function downloadId(card,button){
  const old=button.textContent;button.disabled=true;button.textContent='Preparing…';
  try{
    syncQr();await new Promise(r=>setTimeout(r,80));
    const d=card.dataset,name=d.cardName||'Member',id=d.cardId||'',role=d.cardRole||'Member',joined=d.cardDate||'',status=d.cardStatus||'Active',website=d.cardWebsite||'';
    const logo=await dataUrl(d.logoSrc||''),photo=await dataUrl(d.photoSrc||'');const qr=qs('[data-id-qr] canvas',card)?.toDataURL('image/png')||qs('[data-id-qr] img',card)?.src||'';
    const logoSvg=logo?`<image href="${xml(logo)}" x="48" y="32" width="92" height="92" preserveAspectRatio="xMidYMid meet"/>`:`<rect x="48" y="32" width="92" height="92" rx="24" fill="#b9dc62"/><text x="94" y="91" text-anchor="middle" font-size="42" font-weight="900" fill="#07543b">97</text>`;
    const photoSvg=photo?`<image href="${xml(photo)}" x="58" y="205" width="210" height="248" preserveAspectRatio="xMidYMid slice" clip-path="url(#photoClip)"/>`:`<rect x="58" y="205" width="210" height="248" rx="24" fill="#e5f4ea"/><text x="163" y="350" text-anchor="middle" font-size="84" font-weight="900" fill="#087d48">${xml(name.charAt(0).toUpperCase())}</text>`;
    const qrSvg=qr?`<image href="${xml(qr)}" x="775" y="295" width="180" height="180"/>`:'';
    const svg=`<svg xmlns="http://www.w3.org/2000/svg" width="1011" height="638" viewBox="0 0 1011 638"><defs><clipPath id="photoClip"><rect x="58" y="205" width="210" height="248" rx="24"/></clipPath><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#00553f"/><stop offset="1" stop-color="#079350"/></linearGradient></defs><rect width="1011" height="638" rx="34" fill="#fff"/><rect width="1011" height="164" rx="34" fill="url(#g)"/><path d="M0 138 C260 210 660 92 1011 150 V195 H0Z" fill="#0cac5a" opacity=".30"/>${logoSvg}<text x="166" y="74" font-family="Arial,sans-serif" font-size="35" font-weight="900" fill="#fff">97 FOUNDATION</text><text x="166" y="108" font-family="Arial,sans-serif" font-size="18" fill="#dff7e8">OFFICIAL ${xml(role.toUpperCase())} IDENTITY CARD</text><rect x="820" y="47" width="142" height="48" rx="24" fill="#e8f8ee"/><circle cx="847" cy="71" r="8" fill="#08a950"/><text x="870" y="79" font-family="Arial,sans-serif" font-size="21" font-weight="800" fill="#087844">${xml(status.toUpperCase())}</text>${photoSvg}<text x="305" y="234" font-family="Arial,sans-serif" font-size="18" font-weight="700" fill="#6e8079">${xml(role.toUpperCase())}</text><text x="305" y="287" font-family="Arial,sans-serif" font-size="42" font-weight="900" fill="#073e35">${xml(name)}</text><text x="305" y="333" font-family="Arial,sans-serif" font-size="19" font-weight="700" fill="#70827b">ID NUMBER</text><text x="305" y="374" font-family="Arial,sans-serif" font-size="31" font-weight="900" fill="#071a27">${xml(id)}</text><line x1="305" y1="398" x2="710" y2="398" stroke="#dce9e3" stroke-width="2"/><text x="305" y="438" font-family="Arial,sans-serif" font-size="17" font-weight="700" fill="#70827b">${role.toLowerCase().includes('volunteer')?'APPROVED':'MEMBER SINCE'}</text><text x="305" y="470" font-family="Arial,sans-serif" font-size="23" font-weight="800" fill="#164a3d">${xml(joined)}</text>${qrSvg}<text x="865" y="505" text-anchor="middle" font-family="Arial,sans-serif" font-size="16" font-weight="800" fill="#087844">SCAN TO VERIFY</text><rect y="565" width="1011" height="73" rx="0" fill="#063f36"/><text x="52" y="610" font-family="Arial,sans-serif" font-size="20" font-weight="700" fill="#fff">Hope • Help • Humanity</text><text x="959" y="610" text-anchor="end" font-family="Arial,sans-serif" font-size="16" fill="#cce9dc">${xml(website||'97 Foundation')}</text></svg>`;
    const blob=new Blob([svg],{type:'image/svg+xml;charset=utf-8'}),url=URL.createObjectURL(blob),img=new Image();
    await new Promise((resolve,reject)=>{img.onload=resolve;img.onerror=reject;img.src=url;});
    const canvas=document.createElement('canvas');canvas.width=1011;canvas.height=638;const ctx=canvas.getContext('2d');ctx.fillStyle='#fff';ctx.fillRect(0,0,canvas.width,canvas.height);ctx.drawImage(img,0,0);URL.revokeObjectURL(url);
    const png=await new Promise(resolve=>canvas.toBlob(resolve,'image/png',1));if(!png)throw new Error('Unable to create ID image.');const a=document.createElement('a');a.href=URL.createObjectURL(png);a.download=`97-foundation-${role.toLowerCase().replace(/\s+/g,'-')}-${id||'id'}.png`;document.body.append(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(a.href),1000);
  }catch(error){alert(error.message||'Unable to download the ID card.');}
  finally{button.disabled=false;button.textContent=old;}
}
function initContent(){syncQr();bindIdActions();setTimeout(()=>qsa('[data-auto-dismiss]').forEach(x=>{x.style.opacity='0';setTimeout(()=>x.remove(),250)}),4200);}
function syncShell(doc){
  document.title=doc.title;
  const nextTitle=qs('[data-portal-title]',doc),title=qs('[data-portal-title]');if(nextTitle&&title)title.textContent=nextTitle.textContent;
  const nextSub=qs('[data-portal-subtitle]',doc),sub=qs('[data-portal-subtitle]');if(nextSub&&sub)sub.textContent=nextSub.textContent;
  const nextNav=qs('.portal-sidebar nav',doc),nav=qs('.portal-sidebar nav');if(nextNav&&nav)nav.innerHTML=nextNav.innerHTML;
  const nextUser=qs('[data-portal-userbar]',doc),user=qs('[data-portal-userbar]');if(nextUser&&user)user.innerHTML=nextUser.innerHTML;
}
async function navigate(href,push=true){
  if(navigating)return;navigating=true;setLoading(true);
  try{const r=await fetch(href,{credentials:'same-origin',cache:'no-store',headers:{Accept:'text/html','X-Requested-With':'97Foundation-Portal-SPA'}});if(!r.ok)throw new Error();const html=await r.text(),doc=new DOMParser().parseFromString(html,'text/html'),next=qs('[data-portal-content]',doc),current=qs('[data-portal-content]');if(!next||!current)throw new Error();current.classList.add('portal-content-leaving');await new Promise(res=>setTimeout(res,150));current.innerHTML=next.innerHTML;current.classList.remove('portal-content-leaving');current.classList.add('portal-content-entering');requestAnimationFrame(()=>requestAnimationFrame(()=>current.classList.remove('portal-content-entering')));syncShell(doc);if(push)history.pushState({portal:true},'',href);scrollTo({top:0,behavior:'auto'});initContent();}
  catch{location.href=href;return;}finally{navigating=false;setTimeout(()=>setLoading(false),180);}
}
document.addEventListener('click',e=>{if(e.defaultPrevented||e.button>0||e.metaKey||e.ctrlKey||e.shiftKey||e.altKey)return;const a=e.target.closest('a[href]');if(!a||a.target==='_blank'||a.hasAttribute('download'))return;let u;try{u=new URL(a.href,location.href)}catch{return;}if(!samePortal(u))return;e.preventDefault();navigate(u.href,true);});
addEventListener('popstate',()=>{if(/\/portal(?:\/|$)/.test(location.pathname))navigate(location.href,false)});
document.addEventListener('DOMContentLoaded',()=>{initContent();setLoading(false);});
})();
