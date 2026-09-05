import{d as k}from"./dayjs.min-BnEQxWup.js";import"./id-Cl4jBuyU.js";import{Q as M}from"./qrcode.vue.esm-BudrFY9T.js";import{Y as g,o as f,c,d as t,t as l,h as A,s as b,b as R,H as V}from"./main-DBUDK-xt.js";import"./_commonjsHelpers-D6-XlEtG.js";const _={key:0,id:"print-payable-receipt-section"},J={key:0,class:"thermal-container"},F={class:"text-center"},W={class:"font-bold",style:{"font-size":"13px"}},q={style:{"font-size":"11px"}},Q={style:{"font-size":"9px"}},X={key:0,style:{"font-size":"9px"}},Z={class:"meta-table"},tt={key:0},et={class:"meta-table"},nt={class:"text-right"},at={class:"font-bold",style:{"font-size":"12px"}},it={class:"text-right"},ot={class:"text-right"},lt={class:"font-bold",style:{color:"#000"}},st={class:"text-right"},rt={style:{"font-size":"9px","font-style":"italic","margin-bottom":"6px"}},dt={style:{"text-align":"center",margin:"6px auto"}},mt={class:"text-center mt-2",style:{"font-size":"9px"}},ut={key:1,class:"kwitansi-frame"},pt={class:"header-table"},gt={style:{width:"58%","vertical-align":"top"}},ft={class:"store-name"},ct={class:"store-address"},bt={key:0,class:"store-address"},yt={style:{width:"42%","vertical-align":"top","text-align":"right"}},xt={class:"kwitansi-meta"},vt={class:"kwitansi-meta",style:{"font-size":"9.5px",color:"#333"}},ht={class:"form-table"},At={class:"form-fill-line"},wt={style:{"font-size":"12px","text-transform":"uppercase"}},Tt={key:0,style:{"font-size":"10.5px",color:"#444","margin-left":"6px"}},kt={class:"terbilang-box"},St={class:"form-fill-line"},Nt={class:"form-fill-line",style:{"font-size":"10.5px"}},zt={key:0},It={key:1},Dt={style:{"margin-left":"8px"}},Mt={class:"footer-table"},Rt={style:{width:"44%","vertical-align":"top"}},Et={class:"nominal-box"},Kt={class:"nominal-value"},Yt={style:{"margin-top":"6px",display:"flex","align-items":"center",gap:"6px",background:"#fafafa",border:"1px solid #ccc",padding:"4px 6px","border-radius":"3px","max-width":"240px"}},$t={style:{border:"1px solid #aaa",padding:"1px",background:"#fff",display:"inline-block"}},Ct={style:{width:"56%","vertical-align":"top"}},Pt={style:{"text-align":"right","font-size":"10px",color:"#333","margin-bottom":"4px"}},Ut={class:"ttd-table"},Bt={style:{width:"50%"}},Ht={class:"ttd-name"},Lt={style:{width:"50%"}},jt={style:{margin:"1px auto",display:"flex","flex-direction":"column","justify-content":"center","align-items":"center","min-height":"36px"}},Gt={style:{border:"1px solid #ccc",padding:"1px",background:"#fff",display:"inline-block"}},Ot={class:"ttd-name"},Qt={__name:"PayableReceiptPrinter",props:{statement:{type:Object,required:!1,default:null},payment:{type:Object,required:!1,default:null},branch:{type:Object,required:!1,default:null},setting:{type:Object,required:!1,default:null},printFormat:{type:String,default:"continuous_form"}},setup(s,{expose:P}){k.locale("id");const a=s,v=o=>new Intl.NumberFormat("id-ID").format(Math.round(o||0)),U=o=>o?k(o).format("DD-MM-YYYY"):"-",B=o=>o?k(o).format("DD-MM-YYYY HH:mm"):"-",E=o=>o?k(o).format("DD MMMM YYYY"):"-",H=o=>{const e=Math.floor(Math.abs(Number(o)||0));if(e===0)return"nol";const r=["","satu","dua","tiga","empat","lima","enam","tujuh","delapan","sembilan","sepuluh","sebelas"];function i(n){return n<12?r[n]:n<20?i(n-10)+" belas":n<100?i(Math.floor(n/10))+" puluh"+(n%10>0?" "+i(n%10):""):n<200?"seratus"+(n-100>0?" "+i(n-100):""):n<1e3?i(Math.floor(n/100))+" ratus"+(n%100>0?" "+i(n%100):""):n<2e3?"seribu"+(n-1e3>0?" "+i(n-1e3):""):n<1e6?i(Math.floor(n/1e3))+" ribu"+(n%1e3>0?" "+i(n%1e3):""):n<1e9?i(Math.floor(n/1e6))+" juta"+(n%1e6>0?" "+i(n%1e6):""):n<1e12?i(Math.floor(n/1e9))+" miliar"+(n%1e9>0?" "+i(n%1e9):""):n<1e15?i(Math.floor(n/1e12))+" triliun"+(n%1e12>0?" "+i(n%1e12):""):""}return i(e).replace(/\s+/g," ").trim()},K=g(()=>{var e;if(!((e=a.payment)!=null&&e.amount))return"";let o=H(a.payment.amount);return o.charAt(0).toUpperCase()+o.slice(1)+" rupiah"}),N=g(()=>{var o,e,r,i,n,p,m,u,y,w,T;if((e=(o=a.payment)==null?void 0:o.creator)!=null&&e.name)return a.payment.creator.name;if((i=(r=a.payment)==null?void 0:r.user)!=null&&i.name)return a.payment.user.name;if((m=(p=(n=a.payment)==null?void 0:n.creator)==null?void 0:p.employee)!=null&&m.name)return a.payment.creator.employee.name;if((y=(u=a.statement)==null?void 0:u.creator)!=null&&y.name)return a.statement.creator.name;try{const x=JSON.parse(localStorage.getItem("userData")||"{}");if(x!=null&&x.name)return x.name;const h=JSON.parse(localStorage.getItem("user")||"{}");if(h!=null&&h.name)return h.name}catch{}return((T=(w=a.branch)==null?void 0:w.owner)==null?void 0:T.name)||"Admin Toko"}),L=g(()=>{var o,e,r,i;return((e=(o=a.statement)==null?void 0:o.supplier)==null?void 0:e.contact_person)||((i=(r=a.statement)==null?void 0:r.supplier)==null?void 0:i.name)||"Pihak Supplier"}),d=g(()=>{var o;return a.branch||((o=a.statement)==null?void 0:o.branch)||{}}),Y=g(()=>{var r,i,n;if(a.printFormat==="thermal")return!0;const o=((i=(r=a.setting)==null?void 0:r.name)==null?void 0:i.toLowerCase())||"",e=((n=a.setting)==null?void 0:n.width)||"";return o.includes("thermal")||e.includes("58mm")||e.includes("80mm")}),j=g(()=>{var e,r;const o=((r=(e=a.setting)==null?void 0:e.name)==null?void 0:r.toLowerCase())||"";return o.includes("11 inch")||o.includes("11")}),G=g(()=>{var r,i,n;const o=((i=(r=a.setting)==null?void 0:r.name)==null?void 0:i.toLowerCase())||"",e=((n=a.setting)==null?void 0:n.width)||"";return o.includes("a5")||e.includes("210mm")}),O=g(()=>{var m,u,y;if(!a.payment)return"";const o=a.payment.payment_number||"KAS-KELUAR",e=((u=(m=a.statement)==null?void 0:m.supplier)==null?void 0:u.name)||"Supplier",r=d.value.name||"Cabang Utama",i=Number(a.payment.amount||0).toLocaleString("id-ID"),n=Number(((y=a.statement)==null?void 0:y.remaining_amount)||0).toLocaleString("id-ID"),p=U(a.payment.payment_date||a.payment.created_at);return`VERIFIKASI KEABSAHAN TRANSAKSI MS.POS
====================================
Dokumen   : Kuitansi Pembayaran Hutang
No. Kuitansi: ${o}
Supplier  : ${e}
Cabang    : ${r}
Tgl Bayar : ${p}
Jml Bayar : Rp ${i}
Sisa Hutang: Rp ${n}
Metode    : ${a.payment.payment_method||"Kas"}
Status    : PENGELUARAN KAS SAH & TERCATAT RESMI`}),$=g(()=>{var r,i;const o=d.value.name||"Cabang Utama",e=k(((r=a.payment)==null?void 0:r.created_at)||new Date).format("DD/MM/YYYY HH:mm:ss");return`TANDA TANGAN DIGITAL RESMI (DIGITAL SIGNATURE)
===============================================
Penandatangan : ${N.value}
Jabatan       : Petugas Keuangan / Kasir
Unit / Cabang : ${o}
Waktu TTD     : ${e}
Keperluan     : Pengesahan Pembayaran Tagihan (${((i=a.statement)==null?void 0:i.statement_number)||"-"})
Status TTD    : TERTANDA DIGITAL SAH (VERIFIED)`});return P({print:()=>{var n,p,m,u,y,w,T,x,h;const o=document.getElementById("print-payable-receipt-section");if(!o){window.print();return}const e=document.createElement("iframe");e.style.position="fixed",e.style.right="0",e.style.bottom="0",e.style.width="0",e.style.height="0",e.style.border="0",document.body.appendChild(e);let r="";if(Y.value){const S=((n=a.setting)==null?void 0:n.width)||"80mm",z=((p=a.setting)==null?void 0:p.margin_top)??0,I=((m=a.setting)==null?void 0:m.margin_bottom)??0,D=((u=a.setting)==null?void 0:u.margin_left)??0,C=((y=a.setting)==null?void 0:y.margin_right)??0;r=`
      @page {
        size: ${S} auto;
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
        width: ${S};
        color: #000;
        background: #fff;
        padding: ${z||2}mm ${C||3}mm ${I||2}mm ${D||3}mm;
        margin: 0 auto;
      }
      .text-center { text-align: center; }
      .text-right { text-align: right; }
      .font-bold { font-weight: bold; }
      .divider-dashed { border-top: 1px dashed #000; margin: 5px 0; }
      .meta-table { width: 100%; border-collapse: collapse; font-size: 11px; }
      .meta-table td { padding: 1px 0; }
    `}else{const S=((w=a.setting)==null?void 0:w.margin_top)??4,z=((T=a.setting)==null?void 0:T.margin_bottom)??4,I=((x=a.setting)==null?void 0:x.margin_left)??6,D=((h=a.setting)==null?void 0:h.margin_right)??6;r=`
      @page {
        size: ${j.value?"241mm 280mm":G.value?"210mm 148mm":"241mm 140mm"};
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
        padding: ${S}mm ${D}mm ${z}mm ${I}mm;
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
    `}const i=e.contentWindow.document;i.open(),i.write(`
    <!DOCTYPE html>
    <html>
      <head>
        <title></title>
        <style>
          ${r}
        </style>
      </head>
      <body>
        ${o.innerHTML}
      </body>
    </html>
  `),i.close(),setTimeout(()=>{e.contentWindow.focus(),e.contentWindow.print(),setTimeout(()=>{document.body.removeChild(e)},1e3)},400)}}),(o,e)=>{var r,i,n,p,m,u;return s.payment&&s.statement?(f(),c("div",_,[Y.value?(f(),c("div",J,[t("div",F,[t("div",W,l((((r=d.value.owner)==null?void 0:r.name)||d.value.name||"TOKO").toUpperCase()),1),t("div",q,l(d.value.name),1),t("div",Q,l(d.value.address||"-"),1),d.value.phone?(f(),c("div",X,"Telp: "+l(d.value.phone),1)):A("",!0)]),e[14]||(e[14]=t("div",{class:"divider-dashed"},null,-1)),e[15]||(e[15]=t("div",{class:"text-center font-bold",style:{"font-size":"11px"}}," KUITANSI PEMBAYARAN HUTANG ",-1)),e[16]||(e[16]=t("div",{class:"divider-dashed"},null,-1)),t("table",Z,[t("tbody",null,[t("tr",null,[e[0]||(e[0]=t("td",null,"No. Bukti",-1)),t("td",null,": "+l(s.payment.payment_number),1)]),t("tr",null,[e[1]||(e[1]=t("td",null,"Tanggal",-1)),t("td",null,": "+l(B(s.payment.payment_date||s.payment.created_at)),1)]),t("tr",null,[e[3]||(e[3]=t("td",null,"Supplier",-1)),t("td",null,[e[2]||(e[2]=b(": ")),t("strong",null,l((i=s.statement.supplier)==null?void 0:i.name),1)])]),t("tr",null,[e[4]||(e[4]=t("td",null,"No. Tagihan",-1)),t("td",null,": "+l(s.statement.statement_number),1)]),t("tr",null,[e[5]||(e[5]=t("td",null,"Metode",-1)),t("td",null,": "+l(s.payment.payment_method==="bank_transfer"?"Transfer Bank":"Kas Tunai"),1)]),s.payment.bank_account?(f(),c("tr",tt,[e[6]||(e[6]=t("td",null,"Bank",-1)),t("td",null,": "+l(s.payment.bank_account.bank_name),1)])):A("",!0)])]),e[17]||(e[17]=t("div",{class:"divider-dashed"},null,-1)),t("table",et,[t("tbody",null,[t("tr",null,[e[7]||(e[7]=t("td",null,"Total Tagihan",-1)),t("td",nt,"Rp "+l(v(s.statement.total_amount)),1)]),t("tr",at,[e[8]||(e[8]=t("td",null,"DIBAYAR KALI INI",-1)),t("td",it,"Rp "+l(v(s.payment.amount)),1)]),t("tr",null,[e[9]||(e[9]=t("td",null,"Total Sudah Bayar",-1)),t("td",ot,"Rp "+l(v(s.statement.paid_amount)),1)]),t("tr",lt,[e[10]||(e[10]=t("td",null,"Sisa Hutang",-1)),t("td",st,"Rp "+l(v(s.statement.remaining_amount)),1)])])]),e[18]||(e[18]=t("div",{class:"divider-dashed"},null,-1)),t("div",rt," Terbilang: "+l(K.value),1),t("div",dt,[R(M,{value:$.value,size:65,level:"M","render-as":"svg"},null,8,["value"]),e[11]||(e[11]=t("div",{style:{"font-size":"8px",color:"#555","margin-top":"2px"}},"Scan Validasi Pembayaran",-1))]),t("div",mt,[t("div",null,[e[12]||(e[12]=b("Kasir / Admin: ")),t("strong",null,l(N.value),1)]),e[13]||(e[13]=t("div",{style:{"margin-top":"3px"}},"*** Struk ini adalah bukti pembayaran sah ***",-1))])])):(f(),c("div",ut,[t("table",pt,[t("tbody",null,[t("tr",null,[t("td",gt,[t("div",ft,l(((n=d.value.owner)==null?void 0:n.name)||"PT. PAGARUYUNG MITRA PERSADA"),1),t("div",ct,[t("strong",null,l(d.value.name||"Cabang Utama"),1),b(" - "+l(d.value.address||"Jalan Lintas Kilometer 18"),1)]),d.value.phone?(f(),c("div",bt," Telp: "+l(d.value.phone),1)):A("",!0)]),t("td",yt,[e[20]||(e[20]=t("div",{class:"kwitansi-title"}," K U I T A N S I ",-1)),t("div",xt,[e[19]||(e[19]=b(" NO: ")),t("strong",null,l(s.payment.payment_number),1)]),t("div",vt," TGL: "+l(E(s.payment.payment_date||s.payment.created_at)),1)])])])]),e[38]||(e[38]=t("div",{class:"divider-solid"},null,-1)),t("table",ht,[t("tbody",null,[t("tr",null,[e[21]||(e[21]=t("td",{class:"form-label"},"DIBAYARKAN KEPADA",-1)),e[22]||(e[22]=t("td",{class:"form-sep"},":",-1)),t("td",null,[t("div",At,[t("strong",wt,l((p=s.statement.supplier)==null?void 0:p.name),1),(m=s.statement.supplier)!=null&&m.phone?(f(),c("span",Tt,"(Telp: "+l(s.statement.supplier.phone)+")",1)):A("",!0)])])]),t("tr",null,[e[23]||(e[23]=t("td",{class:"form-label"},"SEJUMLAH UANG",-1)),e[24]||(e[24]=t("td",{class:"form-sep"},":",-1)),t("td",null,[t("div",kt," *** "+l(K.value)+" *** ",1)])]),t("tr",null,[e[28]||(e[28]=t("td",{class:"form-label"},"UNTUK PEMBAYARAN",-1)),e[29]||(e[29]=t("td",{class:"form-sep"},":",-1)),t("td",null,[t("div",St,[t("span",null,[e[25]||(e[25]=b("Cicilan / Pelunasan Hutang Tagihan Supplier Periode ")),t("strong",null,l(s.statement.period_month),1),e[26]||(e[26]=b(" (No. Tagihan: ")),t("strong",null,l(s.statement.statement_number),1),e[27]||(e[27]=b(")"))])])])]),t("tr",null,[e[30]||(e[30]=t("td",{class:"form-label"},"METODE PEMBAYARAN",-1)),e[31]||(e[31]=t("td",{class:"form-sep"},":",-1)),t("td",null,[t("div",Nt,[t("strong",null,l(s.payment.payment_method==="bank_transfer"?"Transfer Bank":"Kas Tunai"),1),s.payment.bank_account?(f(),c("span",zt," - "+l(s.payment.bank_account.bank_name)+" ("+l(s.payment.bank_account.account_number)+" a/n "+l(s.payment.bank_account.account_name)+") ",1)):A("",!0),s.payment.reference_number?(f(),c("span",It," | Ref: "+l(s.payment.reference_number),1)):A("",!0),t("span",Dt,[b(" | Total: Rp "+l(v(s.statement.total_amount))+" | Sisa: ",1),t("strong",{style:V({color:s.statement.remaining_amount>0?"#b91c1c":"#15803d"})},"Rp "+l(v(s.statement.remaining_amount)),5)])])])])])]),e[39]||(e[39]=t("div",{class:"divider-solid"},null,-1)),t("table",Mt,[t("tbody",null,[t("tr",null,[t("td",Rt,[t("div",Et,[e[32]||(e[32]=t("div",{class:"nominal-label"},"JUMLAH DIBAYAR:",-1)),t("div",Kt,"RP. "+l(v(s.payment.amount)),1)]),t("div",Yt,[t("div",$t,[R(M,{value:O.value,size:32,level:"M","render-as":"svg"},null,8,["value"])]),e[33]||(e[33]=t("div",{style:{"font-size":"7.5px",color:"#333","line-height":"1.2"}},[t("strong",null,"BUKTI TRANSAKSI SAH"),t("br"),b(" Scan untuk verifikasi pengeluaran kas ")],-1))])]),t("td",Ct,[t("div",Pt,l(d.value.city||((u=d.value.name)==null?void 0:u.replace("Cabang ",""))||"Duri")+", "+l(E(s.payment.payment_date||s.payment.created_at)),1),t("table",Ut,[t("tbody",null,[t("tr",null,[t("td",Bt,[e[34]||(e[34]=t("div",{style:{"font-size":"10px","margin-bottom":"2px"}},"Penerima (Supplier),",-1)),e[35]||(e[35]=t("div",{style:{height:"36px",display:"flex","align-items":"center","justify-content":"center"}},[t("span",{style:{"font-size":"8px",color:"#999"}},"( Cap / TTD )")],-1)),t("div",Ht,"( "+l(L.value)+" )",1)]),t("td",Lt,[e[37]||(e[37]=t("div",{style:{"font-size":"10px","margin-bottom":"2px"}},"Kasir / Admin,",-1)),t("div",jt,[t("div",Gt,[R(M,{value:$.value,size:34,level:"M","render-as":"svg"},null,8,["value"])]),e[36]||(e[36]=t("div",{class:"badge-digital"},"[TERTANDA DIGITAL]",-1))]),t("div",Ot," ( "+l(N.value)+" ) ",1)])])])])])])])])]))])):A("",!0)}}};export{Qt as default};
