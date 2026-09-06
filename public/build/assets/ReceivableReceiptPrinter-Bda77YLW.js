import{d as h}from"./dayjs.min-BnEQxWup.js";import"./id-Cl4jBuyU.js";import{Q as z}from"./qrcode.vue.esm-X6k4uv8y.js";import{Y as m,o as f,c as y,d as t,t as i,h as P,s as p,b as M,H as O}from"./main-CMGL0f8O.js";import"./_commonjsHelpers-D6-XlEtG.js";const q={key:0,id:"print-receivable-receipt",class:"receivable-print-wrapper"},F={key:0,class:"thermal-container"},Q={class:"text-center"},J={class:"font-bold",style:{"font-size":"13px"}},W={style:{"font-size":"11px"}},X={style:{"font-size":"9px"}},Z={key:0,style:{"font-size":"9px"}},tt={class:"meta-table"},et={class:"meta-table"},nt={class:"text-right"},at={class:"font-bold",style:{"font-size":"12px"}},lt={class:"text-right"},it={class:"text-right"},ot={class:"font-bold",style:{color:"#000"}},st={class:"text-right"},rt={style:{"font-size":"9px","font-style":"italic","margin-bottom":"6px"}},dt={style:{"text-align":"center",margin:"6px auto"}},mt={class:"text-center mt-2",style:{"font-size":"9px"}},ut={key:1,class:"kwitansi-frame"},ct={class:"header-table"},pt={style:{width:"58%","vertical-align":"top"}},gt={class:"store-name"},bt={class:"store-address"},ft={key:0,class:"store-address"},yt={style:{width:"42%","vertical-align":"top","text-align":"right"}},vt={class:"kwitansi-meta"},xt={class:"kwitansi-meta",style:{"font-size":"9.5px",color:"#333"}},ht={class:"form-table"},Pt={class:"form-fill-line"},At={style:{"font-size":"12px","text-transform":"uppercase"}},wt={key:0,style:{"font-size":"10.5px",color:"#444","margin-left":"6px"}},Tt={class:"terbilang-box"},kt={class:"form-fill-line"},Nt={class:"form-fill-line",style:{"font-size":"10.5px"}},It={key:0},St={style:{"margin-left":"8px"}},zt={class:"footer-table"},Mt={style:{width:"44%","vertical-align":"top"}},Dt={class:"nominal-box"},Rt={class:"nominal-value"},Et={style:{"margin-top":"6px",display:"flex","align-items":"center",gap:"6px",background:"#f8fafc",border:"1px solid #ccc",padding:"4px 6px","border-radius":"3px","max-width":"240px"}},$t={style:{border:"1px solid #aaa",padding:"1px",background:"#fff",display:"inline-block"}},Ct={style:{width:"56%","vertical-align":"top"}},Yt={style:{"text-align":"right","font-size":"10px",color:"#333","margin-bottom":"4px"}},Bt={class:"ttd-table"},Ut={style:{width:"50%"}},_t={class:"ttd-name"},Kt={style:{width:"50%"}},Ht={style:{margin:"1px auto",display:"flex","flex-direction":"column","justify-content":"center","align-items":"center","min-height":"36px"}},Lt={style:{border:"1px solid #ccc",padding:"1px",background:"#fff",display:"inline-block"}},jt={class:"ttd-name"},Jt={__name:"ReceivableReceiptPrinter",props:{receivable:{type:Object,required:!1,default:null},lastPayment:{type:Object,required:!1,default:null},branch:{type:Object,required:!1,default:null},setting:{type:Object,required:!1,default:null},printFormat:{type:String,default:"continuous_form"}},setup(o,{expose:K}){h.locale("id");const s=o,g=a=>new Intl.NumberFormat("id-ID").format(Math.round(a||0)),D=a=>a?h(a).format("DD-MM-YYYY"):"-",H=a=>a?h(a).format("DD-MM-YYYY HH:mm"):"-",R=a=>a?h(a).format("DD MMMM YYYY"):"-",A=m(()=>{if(!s.receivable)return 0;const a=Number(s.receivable.amount_due)||0,e=Number(s.receivable.amount_paid)||0;return Math.max(0,a-e)}),L=a=>{const e=Math.floor(Math.abs(Number(a)||0));if(e===0)return"nol";const r=["","satu","dua","tiga","empat","lima","enam","tujuh","delapan","sembilan","sepuluh","sebelas"];function l(n){return n<12?r[n]:n<20?l(n-10)+" belas":n<100?l(Math.floor(n/10))+" puluh"+(n%10>0?" "+l(n%10):""):n<200?"seratus"+(n-100>0?" "+l(n-100):""):n<1e3?l(Math.floor(n/100))+" ratus"+(n%100>0?" "+l(n%100):""):n<2e3?"seribu"+(n-1e3>0?" "+l(n-1e3):""):n<1e6?l(Math.floor(n/1e3))+" ribu"+(n%1e3>0?" "+l(n%1e3):""):n<1e9?l(Math.floor(n/1e6))+" juta"+(n%1e6>0?" "+l(n%1e6):""):n<1e12?l(Math.floor(n/1e9))+" miliar"+(n%1e9>0?" "+l(n%1e9):""):n<1e15?l(Math.floor(n/1e12))+" triliun"+(n%1e12>0?" "+l(n%1e12):""):""}return l(e).replace(/\s+/g," ").trim()},E=m(()=>{var e;if(!((e=s.lastPayment)!=null&&e.amount))return"";let a=L(s.lastPayment.amount);return a.charAt(0).toUpperCase()+a.slice(1)+" rupiah"}),k=m(()=>{var a,e;if((e=(a=s.lastPayment)==null?void 0:a.user)!=null&&e.name)return s.lastPayment.user.name;try{const r=JSON.parse(localStorage.getItem("userData")||"{}");if(r!=null&&r.name)return r.name}catch{}return"Petugas Kasir"}),w=m(()=>{var a,e;return((e=(a=s.receivable)==null?void 0:a.customer)==null?void 0:e.name)||"Pelanggan Toko"}),d=m(()=>{var a,e;return s.branch||((e=(a=s.receivable)==null?void 0:a.sale)==null?void 0:e.branch)||{}}),$=m(()=>{var r,l,n;if(s.printFormat==="thermal")return!0;const a=((l=(r=s.setting)==null?void 0:r.name)==null?void 0:l.toLowerCase())||"",e=((n=s.setting)==null?void 0:n.width)||"";return a.includes("thermal")||e.includes("58mm")||e.includes("80mm")}),j=m(()=>{var e,r;const a=((r=(e=s.setting)==null?void 0:e.name)==null?void 0:r.toLowerCase())||"";return a.includes("11 inch")||a.includes("11")}),V=m(()=>{var r,l,n;const a=((l=(r=s.setting)==null?void 0:r.name)==null?void 0:l.toLowerCase())||"",e=((n=s.setting)==null?void 0:n.width)||"";return a.includes("a5")||e.includes("210mm")}),G=m(()=>{var u,c;if(!s.lastPayment)return"";const a=((c=(u=s.receivable)==null?void 0:u.sale)==null?void 0:c.invoice_number)||"PIUTANG",e=w.value,r=d.value.name||"Cabang Utama",l=Number(s.lastPayment.amount||0).toLocaleString("id-ID"),n=Number(A.value||0).toLocaleString("id-ID"),b=D(s.lastPayment.payment_date||s.lastPayment.created_at);return`VERIFIKASI KEABSAHAN TRANSAKSI MS.POS
====================================
Dokumen   : Kuitansi Pembayaran Piutang
No. Nota  : ${a}
Pelanggan : ${e}
Cabang    : ${r}
Tgl Bayar : ${b}
Jml Bayar : Rp ${l}
Sisa Bon  : Rp ${n}
Metode    : ${s.lastPayment.payment_method||"Kas"}
Status    : PEMBAYARAN SAH & TERCATAT RESMI`}),C=m(()=>{var r,l,n;const a=d.value.name||"Cabang Utama",e=h(((r=s.lastPayment)==null?void 0:r.created_at)||new Date).format("DD/MM/YYYY HH:mm:ss");return`TANDA TANGAN DIGITAL RESMI (DIGITAL SIGNATURE)
===============================================
Penandatangan : ${k.value}
Jabatan       : Petugas Kasir / Finance
Unit / Cabang : ${a}
Waktu TTD     : ${e}
Keperluan     : Pengesahan Pembayaran Piutang (${((n=(l=s.receivable)==null?void 0:l.sale)==null?void 0:n.invoice_number)||"-"})
Status TTD    : TERTANDA DIGITAL SAH (VERIFIED)`});return K({print:()=>{var n,b,u,c,v,x,Y,B,U;const a=document.getElementById("print-receivable-receipt");if(!a){window.print();return}const e=document.createElement("iframe");e.style.position="fixed",e.style.right="0",e.style.bottom="0",e.style.width="0",e.style.height="0",e.style.border="0",document.body.appendChild(e);let r="";if($.value){const T=((n=s.setting)==null?void 0:n.width)||"80mm",N=((b=s.setting)==null?void 0:b.margin_top)??0,I=((u=s.setting)==null?void 0:u.margin_bottom)??0,S=((c=s.setting)==null?void 0:c.margin_left)??0,_=((v=s.setting)==null?void 0:v.margin_right)??0;r=`
      @page {
        size: ${T} auto;
        margin: 0mm !important;
      }
      @media print {
        @page { margin: 0mm !important; }
        html, body { margin: 0mm !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      }
      * { box-sizing: border-box; }
      body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 11px;
        line-height: 1.2;
        width: ${T};
        color: #000;
        background: #fff;
        padding: ${N||2}mm ${_||3}mm ${I||2}mm ${S||3}mm;
        margin: 0 auto;
      }
      .text-center { text-align: center; }
      .text-right { text-align: right; }
      .font-bold { font-weight: bold; }
      .divider-dashed { border-top: 1px dashed #000; margin: 5px 0; }
      .meta-table { width: 100%; border-collapse: collapse; font-size: 11px; }
      .meta-table td { padding: 1px 0; }
    `}else{const T=((x=s.setting)==null?void 0:x.margin_top)??4,N=((Y=s.setting)==null?void 0:Y.margin_bottom)??4,I=((B=s.setting)==null?void 0:B.margin_left)??6,S=((U=s.setting)==null?void 0:U.margin_right)??6;r=`
      @page {
        size: ${j.value?"241mm 280mm":V.value?"210mm 148mm":"241mm 140mm"};
        margin: 0mm !important;
      }
      @media print {
        @page { margin: 0mm !important; }
        html, body { margin: 0mm !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      }
      * { box-sizing: border-box; }
      body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif, 'Courier New', monospace;
        font-size: 11px;
        line-height: 1.3;
        width: 100%;
        color: #000;
        background: #fff;
        padding: ${T}mm ${S}mm ${N}mm ${I}mm;
        margin: 0;
      }
      .kwitansi-frame {
        border: 1.5px solid #000;
        border-radius: 4px;
        padding: 8px 12px;
        background: #fff;
      }
      .header-table {
        width: 100%;
        border-collapse: collapse;
      }
      .store-name {
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        color: #000;
        letter-spacing: 0.5px;
      }
      .store-address {
        font-size: 9.5px;
        color: #333;
        margin-top: 1px;
      }
      .kwitansi-title {
        font-size: 16px;
        font-weight: 900;
        letter-spacing: 2px;
        color: #000;
        text-align: right;
        margin: 0;
      }
      .kwitansi-meta {
        font-size: 10.5px;
        color: #000;
        text-align: right;
        margin-top: 2px;
        font-family: monospace;
      }
      .divider-solid {
        border-bottom: 1.5px solid #000;
        margin: 6px 0;
      }
      .form-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 4px;
        font-size: 11px;
      }
      .form-table td {
        vertical-align: top;
      }
      .form-label {
        width: 170px;
        font-weight: bold;
        color: #000;
        white-space: nowrap;
      }
      .form-sep {
        width: 10px;
        text-align: center;
        font-weight: bold;
      }
      .form-fill-line {
        border-bottom: 1px dotted #555;
        padding-bottom: 1px;
        width: 100%;
        display: block;
      }
      .terbilang-box {
        background: #f8fafc;
        border: 1px dashed #666;
        padding: 4px 8px;
        font-style: italic;
        font-weight: bold;
        font-size: 11px;
        color: #000;
        border-radius: 3px;
        display: block;
      }
      .footer-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 6px;
      }
      .nominal-box {
        border: 1.5px solid #000;
        background: #f8fafc;
        padding: 4px 10px;
        display: inline-block;
        border-radius: 3px;
      }
      .nominal-label {
        font-size: 9px;
        font-weight: bold;
        color: #333;
        letter-spacing: 0.5px;
      }
      .nominal-value {
        font-size: 15px;
        font-weight: 800;
        font-family: monospace, sans-serif;
        color: #000;
      }
      .ttd-table {
        width: 100%;
        text-align: center;
        border-collapse: collapse;
      }
      .ttd-table td {
        vertical-align: top;
        padding: 0 6px;
      }
      .ttd-name {
        font-weight: bold;
        font-size: 10px;
        color: #000;
      }
      .badge-digital {
        font-size: 6px;
        color: #16a34a;
        font-weight: bold;
        margin-top: 1px;
      }
    `}const l=e.contentWindow.document;l.open(),l.write(`
    <!DOCTYPE html>
    <html>
      <head>
        <title></title>
        <style>
          ${r}
        </style>
      </head>
      <body>
        ${a.innerHTML}
      </body>
    </html>
  `),l.close(),setTimeout(()=>{e.contentWindow.focus(),e.contentWindow.print(),setTimeout(()=>{document.body.removeChild(e)},1e3)},400)}}),(a,e)=>{var r,l,n,b,u,c,v,x;return o.receivable&&o.lastPayment?(f(),y("div",q,[$.value?(f(),y("div",F,[t("div",Q,[t("div",J,i((((r=d.value.owner)==null?void 0:r.name)||d.value.name||"PERUSAHAAN").toUpperCase()),1),t("div",W,i(d.value.name||"Cabang Toko"),1),t("div",X,i(d.value.address||"-"),1),d.value.phone?(f(),y("div",Z," Telp: "+i(d.value.phone),1)):P("",!0)]),e[12]||(e[12]=t("div",{class:"divider-dashed"},null,-1)),e[13]||(e[13]=t("div",{class:"text-center font-bold",style:{"font-size":"11px"}}," KUITANSI PENERIMAAN PIUTANG ",-1)),e[14]||(e[14]=t("div",{class:"divider-dashed"},null,-1)),t("table",tt,[t("tbody",null,[t("tr",null,[e[0]||(e[0]=t("td",null,"No. Nota",-1)),t("td",null,": "+i((l=o.receivable.sale)==null?void 0:l.invoice_number),1)]),t("tr",null,[e[1]||(e[1]=t("td",null,"Tanggal",-1)),t("td",null,": "+i(H(o.lastPayment.payment_date||o.lastPayment.created_at)),1)]),t("tr",null,[e[3]||(e[3]=t("td",null,"Pelanggan",-1)),t("td",null,[e[2]||(e[2]=p(": ")),t("strong",null,i(w.value),1)])]),t("tr",null,[e[4]||(e[4]=t("td",null,"Metode",-1)),t("td",null,": "+i(o.lastPayment.payment_method==="bank_transfer"?"Transfer Bank":o.lastPayment.payment_method==="qris"?"QRIS":"Kas Tunai"),1)])])]),e[15]||(e[15]=t("div",{class:"divider-dashed"},null,-1)),t("table",et,[t("tbody",null,[t("tr",null,[e[5]||(e[5]=t("td",null,"Total Piutang",-1)),t("td",nt,"Rp "+i(g(o.receivable.amount_due)),1)]),t("tr",at,[e[6]||(e[6]=t("td",null,"DIBAYAR KALI INI",-1)),t("td",lt,"Rp "+i(g(o.lastPayment.amount)),1)]),t("tr",null,[e[7]||(e[7]=t("td",null,"Total Sudah Bayar",-1)),t("td",it,"Rp "+i(g(o.receivable.amount_paid)),1)]),t("tr",ot,[e[8]||(e[8]=t("td",null,"Sisa Piutang",-1)),t("td",st,"Rp "+i(g(A.value)),1)])])]),e[16]||(e[16]=t("div",{class:"divider-dashed"},null,-1)),t("div",rt," Terbilang: "+i(E.value),1),t("div",dt,[M(z,{value:C.value,size:65,level:"M","render-as":"svg"},null,8,["value"]),e[9]||(e[9]=t("div",{style:{"font-size":"8px",color:"#555","margin-top":"2px"}},"Scan Validasi Pembayaran",-1))]),t("div",mt,[t("div",null,[e[10]||(e[10]=p("Kasir: ")),t("strong",null,i(k.value),1)]),e[11]||(e[11]=t("div",{style:{"margin-top":"3px"}},"*** Bukti Pembayaran Sah ***",-1))])])):(f(),y("div",ut,[t("table",ct,[t("tbody",null,[t("tr",null,[t("td",pt,[t("div",gt,i(((n=d.value.owner)==null?void 0:n.name)||d.value.name||"PERUSAHAAN"),1),t("div",bt,[t("strong",null,i(d.value.name||"Cabang Toko"),1),p(" - "+i(d.value.address||"-"),1)]),d.value.phone?(f(),y("div",ft," Telp: "+i(d.value.phone),1)):P("",!0)]),t("td",yt,[e[18]||(e[18]=t("div",{class:"kwitansi-title"}," K U I T A N S I ",-1)),t("div",vt,[e[17]||(e[17]=p(" NO: ")),t("strong",null,i((b=o.receivable.sale)==null?void 0:b.invoice_number),1)]),t("div",xt," TGL: "+i(R(o.lastPayment.payment_date||o.lastPayment.created_at)),1)])])])]),e[34]||(e[34]=t("div",{class:"divider-solid"},null,-1)),t("table",ht,[t("tbody",null,[t("tr",null,[e[19]||(e[19]=t("td",{class:"form-label"},"TELAH DITERIMA DARI",-1)),e[20]||(e[20]=t("td",{class:"form-sep"},":",-1)),t("td",null,[t("div",Pt,[t("strong",At,i(w.value),1),(u=o.receivable.customer)!=null&&u.phone?(f(),y("span",wt,"(Telp: "+i(o.receivable.customer.phone)+")",1)):P("",!0)])])]),t("tr",null,[e[21]||(e[21]=t("td",{class:"form-label"},"SEJUMLAH UANG",-1)),e[22]||(e[22]=t("td",{class:"form-sep"},":",-1)),t("td",null,[t("div",Tt," *** "+i(E.value)+" *** ",1)])]),t("tr",null,[e[24]||(e[24]=t("td",{class:"form-label"},"UNTUK PEMBAYARAN",-1)),e[25]||(e[25]=t("td",{class:"form-sep"},":",-1)),t("td",null,[t("div",kt,[t("span",null,[e[23]||(e[23]=p("Cicilan / Pelunasan Piutang Transaksi Bon No: ")),t("strong",null,i((c=o.receivable.sale)==null?void 0:c.invoice_number),1),p(" (Tgl Bon: "+i(D(((v=o.receivable.sale)==null?void 0:v.date)||o.receivable.created_at))+")",1)])])])]),t("tr",null,[e[26]||(e[26]=t("td",{class:"form-label"},"METODE PEMBAYARAN",-1)),e[27]||(e[27]=t("td",{class:"form-sep"},":",-1)),t("td",null,[t("div",Nt,[t("strong",null,i(o.lastPayment.payment_method==="bank_transfer"||o.lastPayment.payment_method==="transfer"?"Transfer Bank":o.lastPayment.payment_method==="qris"?"QRIS":"Kas Tunai"),1),o.lastPayment.bank_account||o.lastPayment.bank_name?(f(),y("span",It," - "+i(o.lastPayment.bank_account?`${o.lastPayment.bank_account.bank_name} (${o.lastPayment.bank_account.account_number} a/n ${o.lastPayment.bank_account.account_name})`:o.lastPayment.bank_name),1)):P("",!0),t("span",St,[p(" | Total: Rp "+i(g(o.receivable.amount_due))+" | Sisa: ",1),t("strong",{style:O({color:A.value>0?"#b91c1c":"#15803d"})},"Rp "+i(g(A.value)),5)])])])])])]),e[35]||(e[35]=t("div",{class:"divider-solid"},null,-1)),t("table",zt,[t("tbody",null,[t("tr",null,[t("td",Mt,[t("div",Dt,[e[28]||(e[28]=t("div",{class:"nominal-label"},"JUMLAH DIBAYAR:",-1)),t("div",Rt,"RP. "+i(g(o.lastPayment.amount)),1)]),t("div",Et,[t("div",$t,[M(z,{value:G.value,size:32,level:"M","render-as":"svg"},null,8,["value"])]),e[29]||(e[29]=t("div",{style:{"font-size":"7.5px",color:"#333","line-height":"1.2"}},[t("strong",null,"BUKTI TRANSAKSI SAH"),t("br"),p(" Scan untuk verifikasi penerimaan piutang ")],-1))])]),t("td",Ct,[t("div",Yt,i(d.value.city||((x=d.value.name)==null?void 0:x.replace("Cabang ",""))||"Duri")+", "+i(R(o.lastPayment.payment_date||o.lastPayment.created_at)),1),t("table",Bt,[t("tbody",null,[t("tr",null,[t("td",Ut,[e[30]||(e[30]=t("div",{style:{"font-size":"10px","margin-bottom":"2px"}},"Pelanggan (Penyetor),",-1)),e[31]||(e[31]=t("div",{class:"ttd-space",style:{height:"36px",display:"flex","align-items":"center","justify-content":"center"}},[t("span",{style:{"font-size":"8px",color:"#999"}},"( Tanda Tangan )")],-1)),t("div",_t,"( "+i(w.value)+" )",1)]),t("td",Kt,[e[33]||(e[33]=t("div",{style:{"font-size":"10px","margin-bottom":"2px"}},"Kasir / Penerima,",-1)),t("div",Ht,[t("div",Lt,[M(z,{value:C.value,size:34,level:"M","render-as":"svg"},null,8,["value"])]),e[32]||(e[32]=t("div",{class:"badge-digital"},"[TERTANDA DIGITAL]",-1))]),t("div",jt," ( "+i(k.value)+" ) ",1)])])])])])])])])]))])):P("",!0)}}};export{Jt as default};
