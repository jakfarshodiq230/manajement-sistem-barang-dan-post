import{d as S}from"./dayjs.min-BnEQxWup.js";import"./id-Cl4jBuyU.js";import{Q as L}from"./qrcode.vue.esm-jjpQRTzI.js";import{Y as p,o as b,c as y,d as t,t as n,h as D,s as h,F as Y,i as q,b as C,l as lt}from"./main-CEcE2vbN.js";import"./_commonjsHelpers-D6-XlEtG.js";const rt={key:0,id:"print-goods-receipt-section",class:"goods-receipt-print-wrapper"},dt={key:0,class:"thermal-container"},at={class:"text-center"},ut={class:"font-bold",style:{"font-size":"13px"}},pt={style:{"font-size":"11px"}},gt={style:{"font-size":"9px"}},ct={key:0,style:{"font-size":"9px"}},mt={class:"meta-table"},ft={style:{width:"100%","font-size":"10.5px"}},xt={colspan:"2",style:{"padding-top":"2px"}},bt={style:{"font-size":"9.5px",color:"#444"}},yt={key:0,style:{"font-size":"9.5px",color:"#777"}},ht={style:{"text-align":"right","vertical-align":"bottom","font-weight":"bold"}},vt={class:"meta-table"},Rt={class:"text-right"},Nt={key:0},_t={class:"text-right"},wt={key:1},At={class:"text-right"},Tt={class:"font-bold",style:{"font-size":"12px"}},zt={class:"text-right"},Pt={style:{"font-size":"9px","font-style":"italic","margin-bottom":"6px"}},St={style:{"text-align":"center",margin:"6px auto"}},It={class:"text-center mt-2",style:{"font-size":"9px"}},Mt={key:1,class:"cf-container"},Dt={style:{width:"100%","border-collapse":"collapse"}},kt={style:{width:"48%","vertical-align":"top"}},Et={class:"font-bold",style:{"font-size":"13px","letter-spacing":"0.5px","text-transform":"uppercase"}},Ut={style:{"font-size":"9.5px","line-height":"1.25","margin-top":"2px"}},Gt={style:{width:"30%","text-align":"right","vertical-align":"top","font-size":"8.5px","line-height":"1.2"}},Ot={style:{"font-style":"italic"}},$t={class:"font-bold",style:{"font-size":"13px","letter-spacing":"1px","margin-top":"3px"}},Lt={style:{"margin-top":"3px","font-size":"8.5px",color:"#333"}},Ct={class:"meta-box-table",style:{"margin-bottom":"2px"}},Kt={style:{width:"52%","vertical-align":"top","padding-right":"8px"}},Bt={style:{width:"100%","border-collapse":"collapse","font-size":"10.5px"}},Ft={style:{"font-size":"11.5px"}},Ht={style:{"margin-left":"20px"}},Vt={style:{width:"48%","vertical-align":"top","padding-left":"8px"}},jt={style:{width:"100%","border-collapse":"collapse","font-size":"10.5px"}},Yt={style:{"font-size":"11px"}},qt={class:"faktur-grid-table"},Jt={style:{"text-align":"center"}},Qt={class:"font-bold",style:{"font-size":"10.5px"}},Wt={style:{"font-size":"10px",color:"#222"}},Xt={style:{"text-align":"center","font-weight":"bold"}},Zt={style:{"text-align":"right","font-family":"monospace"}},te={style:{"text-align":"center","font-size":"10px"}},ee={style:{"text-align":"right","font-family":"monospace"}},oe={style:{"text-align":"right","font-family":"monospace","font-weight":"bold"}},ne={style:{width:"100%","border-collapse":"collapse","margin-top":"2px"}},se={style:{width:"58%","vertical-align":"top","padding-right":"12px"}},ie={style:{"font-size":"9.5px","margin-bottom":"2px"}},le={style:{"font-size":"9px",color:"#333","margin-bottom":"6px"}},re={style:{display:"flex","align-items":"center",gap:"8px","margin-bottom":"6px",background:"#fafafa",border:"1px solid #ddd",padding:"4px 6px","border-radius":"4px"}},de={style:{border:"1px solid #ccc",padding:"1px",background:"#fff",display:"inline-block"}},ae={style:{width:"100%","text-align":"center","font-size":"10px","margin-top":"4px"}},ue={style:{width:"50%","vertical-align":"top"}},pe={class:"font-bold",style:{"font-size":"10px","margin-bottom":"2px"}},ge={style:{width:"50%","vertical-align":"top"}},ce={style:{margin:"1px auto",display:"flex","flex-direction":"column","justify-content":"center","min-height":"36px","align-items":"center"}},me={class:"font-bold"},fe={style:{width:"42%","vertical-align":"top"}},xe={class:"summary-table"},be={style:{"text-align":"right","font-family":"monospace","font-weight":"bold","font-size":"11px"}},ye={style:{"text-align":"right","font-family":"monospace","font-size":"11px"}},he={style:{"border-top":"1px solid #000","border-bottom":"1.5px solid #000"}},ve={style:{"text-align":"right","font-family":"monospace","font-weight":"bold","font-size":"12px",padding:"3px 0"}},Re={style:{"text-align":"right","font-family":"monospace","font-size":"10.5px","padding-top":"4px"}},Ne={style:{"text-align":"right","font-family":"monospace","font-size":"10.5px"}},_e={style:{"text-align":"right","font-family":"monospace","font-size":"10.5px"}},Ie={__name:"GoodsReceiptPrinter",props:{goodsReceipt:{type:Object,required:!1,default:null},branch:{type:Object,required:!1,default:null},setting:{type:Object,required:!1,default:null},printFormat:{type:String,default:"continuous_form"}},setup(a,{expose:J}){S.locale("id");const i=a,g=o=>new Intl.NumberFormat("id-ID").format(Math.round(o||0)),I=o=>o?S(o).format("DD-MM-YYYY"):"-",Q=p(()=>{var l,s;if(!((l=i.goodsReceipt)!=null&&l.date)||!((s=i.goodsReceipt)!=null&&s.due_date))return 0;const o=S(i.goodsReceipt.date),r=S(i.goodsReceipt.due_date).diff(o,"day");return r>=0?r:0}),W=o=>{const e=Math.floor(Math.abs(Number(o)||0));if(e===0)return"nol";const r=["","satu","dua","tiga","empat","lima","enam","tujuh","delapan","sembilan","sepuluh","sebelas"];function l(s){return s<12?r[s]:s<20?l(s-10)+" belas":s<100?l(Math.floor(s/10))+" puluh"+(s%10>0?" "+l(s%10):""):s<200?"seratus"+(s-100>0?" "+l(s-100):""):s<1e3?l(Math.floor(s/100))+" ratus"+(s%100>0?" "+l(s%100):""):s<2e3?"seribu"+(s-1e3>0?" "+l(s-1e3):""):s<1e6?l(Math.floor(s/1e3))+" ribu"+(s%1e3>0?" "+l(s%1e3):""):s<1e9?l(Math.floor(s/1e6))+" juta"+(s%1e6>0?" "+l(s%1e6):""):s<1e12?l(Math.floor(s/1e9))+" miliar"+(s%1e9>0?" "+l(s%1e9):""):s<1e15?l(Math.floor(s/1e12))+" triliun"+(s%1e12>0?" "+l(s%1e12):""):""}return l(e).replace(/\s+/g," ").trim()},k=o=>Number(o.gross_price||o.net_unit_price||0),K=o=>{const e=k(o);let r=Number(o.discount_percent_1)||0,l=Number(o.discount_percent_2)||0,s=Number(o.discount_percent_3)||0,c=Number(o.discount_percent_4)||0,d=Number(o.discount_percent_5)||0;if(o.discount_string&&!r&&!l){const u=String(o.discount_string).split("+").map(m=>parseFloat(m.trim())).filter(m=>!isNaN(m));r=u[0]||0,l=u[1]||0,s=u[2]||0,c=u[3]||0,d=u[4]||0}if(r>0||l>0||s>0||c>0||d>0||Number(o.discount_amount)>0){let u=e;r>0&&(u*=1-r/100),l>0&&(u*=1-l/100),s>0&&(u*=1-s/100),c>0&&(u*=1-c/100),d>0&&(u*=1-d/100);const m=Number(o.discount_amount)||0,x=Number(o.qty_received)||1;return m>0&&x>0&&(u-=m/x),Math.max(0,Math.round(u))}return o.net_unit_price&&Number(o.net_unit_price)>0?Number(o.net_unit_price):e},U=o=>{const e=K(o),r=Number(o.qty_received)||1;return Math.round(e*r)},G=o=>{if(o.discount_string)return o.discount_string;const e=[];return o.discount_percent_1>0&&e.push(`${Number(o.discount_percent_1).toFixed(2)}%`),o.discount_percent_2>0&&e.push(`${Number(o.discount_percent_2).toFixed(2)}%`),o.discount_percent_3>0&&e.push(`${Number(o.discount_percent_3).toFixed(2)}%`),e.length>0?e.join("+ "):o.discount_amount>0?`Rp ${g(o.discount_amount)}`:"-"},B=p(()=>i.goodsReceipt?Number(i.goodsReceipt.subtotal_bruto)>0?Number(i.goodsReceipt.subtotal_bruto):i.goodsReceipt.items?i.goodsReceipt.items.reduce((o,e)=>o+k(e)*Number(e.qty_received||1),0):0:0),T=p(()=>{if(!i.goodsReceipt)return 0;if(Number(i.goodsReceipt.total_amount)>0)return Number(i.goodsReceipt.total_amount);if(!i.goodsReceipt.items)return 0;const o=i.goodsReceipt.items.reduce((e,r)=>e+U(r),0);return Math.max(0,o-(Number(i.goodsReceipt.extra_discount)||0))}),O=p(()=>{if(!i.goodsReceipt)return 0;if(Number(i.goodsReceipt.dpp_amount)>0)return Number(i.goodsReceipt.dpp_amount);const o=Number(i.goodsReceipt.tax_percentage||11);return Math.round(T.value/(1+o/100))}),$=p(()=>i.goodsReceipt?Number(i.goodsReceipt.tax_amount)>0?Number(i.goodsReceipt.tax_amount):Math.max(0,T.value-O.value):0),X=p(()=>i.goodsReceipt?Math.round(O.value*.916666):0),Z=p(()=>{let o=W(T.value);return o.charAt(0).toUpperCase()+o.slice(1)+" rupiah"}),E=p(()=>{var o,e,r,l,s,c;if((e=(o=i.goodsReceipt)==null?void 0:o.user)!=null&&e.name)return i.goodsReceipt.user.name;if((l=(r=i.goodsReceipt)==null?void 0:r.validator)!=null&&l.name)return i.goodsReceipt.validator.name;if((c=(s=i.goodsReceipt)==null?void 0:s.approver)!=null&&c.name)return i.goodsReceipt.approver.name;try{const d=JSON.parse(localStorage.getItem("userData")||"{}");if(d!=null&&d.name)return d.name}catch{}return"Petugas Gudang"}),z=p(()=>{var o,e,r,l;return((e=(o=i.goodsReceipt)==null?void 0:o.purchase_order)==null?void 0:e.supplier)||((l=(r=i.goodsReceipt)==null?void 0:r.purchaseOrder)==null?void 0:l.supplier)||{}}),M=p(()=>z.value.name||"PT. CAPELLA PATRIA UTAMA"),F=p(()=>z.value.address||"JL. SOEKARNO HATTA NO.57 RT.7 RW.12 PEKANBARU"),tt=p(()=>z.value.phone||"0761-7865000"),et=p(()=>z.value.fax||"0761-7865100"),ot=p(()=>z.value.tax_id||z.value.npwp||"0014310932123000"),f=p(()=>{var o,e,r,l;return i.branch||((e=(o=i.goodsReceipt)==null?void 0:o.purchase_order)==null?void 0:e.branch)||((l=(r=i.goodsReceipt)==null?void 0:r.purchaseOrder)==null?void 0:l.branch)||{}}),H=p(()=>{var r,l,s;if(i.printFormat==="thermal")return!0;const o=((l=(r=i.setting)==null?void 0:r.name)==null?void 0:l.toLowerCase())||"",e=((s=i.setting)==null?void 0:s.width)||"";return o.includes("thermal")||e.includes("58mm")||e.includes("80mm")}),nt=p(()=>{var e,r;const o=((r=(e=i.setting)==null?void 0:e.name)==null?void 0:r.toLowerCase())||"";return o.includes("11 inch")||o.includes("11")}),st=p(()=>{var r,l,s;const o=((l=(r=i.setting)==null?void 0:r.name)==null?void 0:l.toLowerCase())||"",e=((s=i.setting)==null?void 0:s.width)||"";return o.includes("a5")||e.includes("210mm")}),it=p(()=>{if(!i.goodsReceipt)return"";const o=i.goodsReceipt.invoice_number_supplier||i.goodsReceipt.receipt_number||"-",e=i.goodsReceipt.receipt_number||"-",r=M.value,l=f.value.name||"Gudang Utama",s=I(i.goodsReceipt.date),c=Number(T.value||0).toLocaleString("id-ID");return`VERIFIKASI KEABSAHAN DOKUMEN MS.POS
====================================
Dokumen   : Faktur Penerimaan Barang
No. GR    : ${e}
No. Faktur: ${o}
Supplier  : ${r}
Cabang    : ${l}
Tanggal   : ${s}
Total     : Rp ${c}
Status    : DOKUMEN SAH & TERCATAT RESMI`}),V=p(()=>{var r,l;const o=f.value.name||"Gudang Utama",e=S(((r=i.goodsReceipt)==null?void 0:r.created_at)||new Date).format("DD/MM/YYYY HH:mm:ss");return`TANDA TANGAN DIGITAL RESMI (DIGITAL SIGNATURE)
===============================================
Penandatangan : ${E.value}
Jabatan       : Petugas Penerimaan Gudang
Unit / Cabang : ${o}
Waktu TTD     : ${e}
Keperluan     : Pengesahan Penerimaan Barang (${((l=i.goodsReceipt)==null?void 0:l.receipt_number)||"-"})
Status TTD    : TERTANDA DIGITAL SAH (VERIFIED)`});return J({print:()=>{var s,c,d,u,m,x,_,w,A;const o=document.getElementById("print-goods-receipt-section");if(!o){window.print();return}const e=document.createElement("iframe");e.style.position="fixed",e.style.right="0",e.style.bottom="0",e.style.width="0",e.style.height="0",e.style.border="0",document.body.appendChild(e);let r="";if(H.value){const v=((s=i.setting)==null?void 0:s.width)||"80mm",R=((c=i.setting)==null?void 0:c.margin_top)??0,N=((d=i.setting)==null?void 0:d.margin_bottom)??0,P=((u=i.setting)==null?void 0:u.margin_left)??0,j=((m=i.setting)==null?void 0:m.margin_right)??0;r=`
      @page {
        size: ${v} auto;
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
        width: ${v};
        color: #000;
        background: #fff;
        padding: ${R||2}mm ${j||3}mm ${N||2}mm ${P||3}mm;
        margin: 0 auto;
      }
      .text-center { text-align: center; }
      .text-right { text-align: right; }
      .font-bold { font-weight: bold; }
      .divider-dashed { border-top: 1px dashed #000; margin: 5px 0; }
      .meta-table { width: 100%; border-collapse: collapse; font-size: 11px; }
      .meta-table td { padding: 1px 0; }
    `}else{const v=((x=i.setting)==null?void 0:x.margin_top)??4,R=((_=i.setting)==null?void 0:_.margin_bottom)??4,N=((w=i.setting)==null?void 0:w.margin_left)??6,P=((A=i.setting)==null?void 0:A.margin_right)??6;r=`
      @page {
        size: ${nt.value?"241mm 280mm":st.value?"210mm 148mm":"241mm 140mm"};
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
        line-height: 1.25;
        width: 100%;
        color: #000;
        background: #fff;
        padding: ${v}mm ${P}mm ${R}mm ${N}mm;
        margin: 0;
      }
      .cf-container {
        width: 100%;
        background: #fff;
        padding: 0;
      }
      .font-bold { font-weight: bold; }
      .text-center { text-align: center; }
      .text-right { text-align: right; }
      .divider-solid { border-top: 1.5px solid #000; margin: 4px 0; }
      .divider-dashed { border-top: 1px dashed #000; margin: 4px 0; }
      .faktur-grid-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10.5px;
        margin-top: 2px;
      }
      .faktur-grid-table th {
        border-top: 1.5px solid #000;
        border-bottom: 1.5px solid #000;
        padding: 4px 3px;
        font-weight: bold;
        font-size: 10px;
        text-transform: uppercase;
      }
      .faktur-grid-table td {
        padding: 2.5px 3px;
        vertical-align: top;
      }
      .meta-box-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10.5px;
      }
      .meta-box-table td {
        padding: 1.5px 2px;
        vertical-align: top;
      }
      .summary-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10.5px;
      }
      .summary-table td {
        padding: 1.5px 2px;
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
        ${o.innerHTML}
      </body>
    </html>
  `),l.close(),setTimeout(()=>{e.contentWindow.focus(),e.contentWindow.print(),setTimeout(()=>{document.body.removeChild(e)},1e3)},400)}}),(o,e)=>{var r,l,s,c;return a.goodsReceipt?(b(),y("div",rt,[H.value?(b(),y("div",dt,[t("div",at,[t("div",ut,n((((r=f.value.owner)==null?void 0:r.name)||f.value.name||"TOKO").toUpperCase()),1),t("div",pt,n(f.value.name),1),t("div",gt,n(f.value.address||"-"),1),f.value.phone?(b(),y("div",ct,"Telp: "+n(f.value.phone),1)):D("",!0)]),e[12]||(e[12]=t("div",{class:"divider-dashed"},null,-1)),e[13]||(e[13]=t("div",{class:"text-center font-bold",style:{"font-size":"11px"}}," BUKTI PENERIMAAN BARANG ",-1)),e[14]||(e[14]=t("div",{class:"divider-dashed"},null,-1)),t("table",mt,[t("tbody",null,[t("tr",null,[e[0]||(e[0]=t("td",null,"No. GR",-1)),t("td",null,": "+n(a.goodsReceipt.receipt_number),1)]),t("tr",null,[e[1]||(e[1]=t("td",null,"No. Faktur",-1)),t("td",null,": "+n(a.goodsReceipt.invoice_number_supplier||"-"),1)]),t("tr",null,[e[2]||(e[2]=t("td",null,"Tanggal",-1)),t("td",null,": "+n(I(a.goodsReceipt.date)),1)]),t("tr",null,[e[4]||(e[4]=t("td",null,"Supplier",-1)),t("td",null,[e[3]||(e[3]=h(": ")),t("strong",null,n(M.value),1)])])])]),e[15]||(e[15]=t("div",{class:"divider-dashed"},null,-1)),t("table",ft,[t("tbody",null,[(b(!0),y(Y,null,q(a.goodsReceipt.items,(d,u)=>{var m,x,_,w,A,v,R,N;return b(),y("tr",{key:d.id||u},[t("td",xt,[t("strong",null,n(((x=(m=d.product_branch)==null?void 0:m.product)==null?void 0:x.code)||((w=(_=d.productBranch)==null?void 0:_.product)==null?void 0:w.code)||""),1),h(" - "+n(((v=(A=d.product_branch)==null?void 0:A.product)==null?void 0:v.name)||((N=(R=d.productBranch)==null?void 0:R.product)==null?void 0:N.name)||d.product_name||"Barang"),1),e[5]||(e[5]=t("br",null,null,-1)),t("span",bt,n(d.qty_received)+" "+n(d.unit||"PCS")+" x "+n(g(k(d))),1),G(d)!=="-"?(b(),y("span",yt," (disc "+n(G(d))+") ",1)):D("",!0)]),t("td",ht,n(g(U(d))),1)])}),128))])]),e[16]||(e[16]=t("div",{class:"divider-dashed"},null,-1)),t("table",vt,[t("tbody",null,[t("tr",null,[e[6]||(e[6]=t("td",null,"Subtotal Bruto",-1)),t("td",Rt,"Rp "+n(g(B.value)),1)]),a.goodsReceipt.extra_discount>0?(b(),y("tr",Nt,[e[7]||(e[7]=t("td",null,"Potongan Tambahan",-1)),t("td",_t,"- Rp "+n(g(a.goodsReceipt.extra_discount)),1)])):D("",!0),$.value>0?(b(),y("tr",wt,[t("td",null,"PPN ("+n(a.goodsReceipt.tax_percentage||11)+"%)",1),t("td",At,"Rp "+n(g($.value)),1)])):D("",!0),t("tr",Tt,[e[8]||(e[8]=t("td",null,"TOTAL FAKTUR",-1)),t("td",zt,"Rp "+n(g(T.value)),1)])])]),e[17]||(e[17]=t("div",{class:"divider-dashed"},null,-1)),t("div",Pt," Terbilang: "+n(Z.value),1),t("div",St,[C(L,{value:V.value,size:65,level:"M","render-as":"svg"},null,8,["value"]),e[9]||(e[9]=t("div",{style:{"font-size":"8px",color:"#555","margin-top":"2px"}},"Scan Validasi Penerimaan",-1))]),t("div",It,[t("div",null,[e[10]||(e[10]=h("Penerima: ")),t("strong",null,n(E.value),1)]),e[11]||(e[11]=t("div",{style:{"margin-top":"3px"}},"*** Dokumen tanda terima sah gudang ***",-1))])])):(b(),y("div",Mt,[t("table",Dt,[t("tbody",null,[t("tr",null,[t("td",kt,[t("div",Et,n(M.value),1),t("div",Ut,[h(n(F.value),1),e[18]||(e[18]=t("br",null,null,-1)),h(" Telp. "+n(tt.value)+", Fax. "+n(et.value),1),e[19]||(e[19]=t("br",null,null,-1)),h(" NPWP/NPPKP: "+n(ot.value)+" Tanggal : "+n(I(a.goodsReceipt.date)),1)])]),e[25]||(e[25]=t("td",{style:{width:"22%","text-align":"center","vertical-align":"top"}},[t("div",{class:"font-bold",style:{"font-size":"16px","letter-spacing":"2px","margin-top":"4px"}}," FAKTUR ")],-1)),t("td",Gt,[t("div",Ot,[e[20]||(e[20]=h(" * Pembayaran dengan giro/cheque harap dicantumkan atas nama")),e[21]||(e[21]=t("br",null,null,-1)),t("strong",null,n(M.value),1),e[22]||(e[22]=h(" dan dianggap sah bila telah diuangkan. "))]),t("div",$t,n(a.goodsReceipt.receipt_number),1),t("div",Lt,[e[23]||(e[23]=t("strong",null,"KANTOR PUSAT :",-1)),e[24]||(e[24]=t("br",null,null,-1)),h(" "+n(F.value),1)])])])])]),e[58]||(e[58]=t("div",{class:"divider-solid"},null,-1)),t("table",Ct,[t("tbody",null,[t("tr",null,[t("td",Kt,[t("table",Bt,[t("tbody",null,[t("tr",null,[e[26]||(e[26]=t("td",{style:{width:"110px","font-weight":"bold","letter-spacing":"1px"}},"N O M O R",-1)),e[27]||(e[27]=t("td",{style:{width:"10px"}},":",-1)),t("td",null,[t("strong",Ft,n(a.goodsReceipt.invoice_number_supplier||a.goodsReceipt.receipt_number),1)])]),t("tr",null,[e[28]||(e[28]=t("td",{style:{"font-weight":"bold"}},"Tgl/Jth Tempo",-1)),e[29]||(e[29]=t("td",null,":",-1)),t("td",null,n(I(a.goodsReceipt.date))+" / "+n(I(a.goodsReceipt.due_date))+" ( "+n(Q.value)+" hari)",1)]),t("tr",null,[e[30]||(e[30]=t("td",{style:{"font-weight":"bold"}},"Kode Sales",-1)),e[31]||(e[31]=t("td",null,":",-1)),t("td",null,n(a.goodsReceipt.sales_name||((s=(l=a.goodsReceipt.purchase_order)==null?void 0:l.supplier)==null?void 0:s.pic_name)||"LK.0001 REZEKI GENESIS"),1)]),t("tr",null,[e[32]||(e[32]=t("td",{style:{"font-weight":"bold"}},"Gudang",-1)),e[33]||(e[33]=t("td",null,":",-1)),t("td",null,[h(n(f.value.name||"G01")+" ",1),t("span",Ht,"SJ : "+n(a.goodsReceipt.delivery_order_number||"-"),1)])])])])]),t("td",Vt,[t("table",jt,[t("tbody",null,[t("tr",null,[e[34]||(e[34]=t("td",{style:{width:"60px","font-weight":"bold"}},"Kepada",-1)),e[35]||(e[35]=t("td",{style:{width:"10px"}},":",-1)),t("td",null,[t("strong",Yt,n((((c=f.value.owner)==null?void 0:c.name)||"PT. PAGARUYUNG MITRA PERSADA").toUpperCase()),1)])]),t("tr",null,[e[36]||(e[36]=t("td",null,null,-1)),e[37]||(e[37]=t("td",null,null,-1)),t("td",null,n(f.value.address||"JALAN LINTAS KILOMETER 18"),1)]),t("tr",null,[e[38]||(e[38]=t("td",null,null,-1)),e[39]||(e[39]=t("td",null,null,-1)),t("td",null,n((f.value.city||"DURI").toUpperCase())+" ("+n(f.value.code||"10.040.02552.01")+")",1)])])])])])])]),t("table",qt,[e[40]||(e[40]=t("thead",null,[t("tr",null,[t("th",{style:{width:"25px","text-align":"center"}},"NO"),t("th",{style:{"text-align":"left"}},"KODEPART/NAMA BRG."),t("th",{style:{width:"45px","text-align":"center"}},"QTY."),t("th",{style:{width:"80px","text-align":"right"}},"HRG/@"),t("th",{style:{width:"90px","text-align":"center"}},"DISCOUNT."),t("th",{style:{width:"80px","text-align":"right"}},"NETTO"),t("th",{style:{width:"105px","text-align":"right"}},"JUMLAH RP(Inc Ppn)")])],-1)),t("tbody",null,[(b(!0),y(Y,null,q(a.goodsReceipt.items,(d,u)=>{var m,x,_,w,A,v,R,N,P;return b(),y("tr",{key:d.id||u},[t("td",Jt,n(u+1),1),t("td",null,[t("div",Qt,n(((x=(m=d.product_branch)==null?void 0:m.product)==null?void 0:x.code)||((w=(_=d.productBranch)==null?void 0:_.product)==null?void 0:w.code)||((A=d.product)==null?void 0:A.code)||"-"),1),t("div",Wt,n(((R=(v=d.product_branch)==null?void 0:v.product)==null?void 0:R.name)||((P=(N=d.productBranch)==null?void 0:N.product)==null?void 0:P.name)||d.product_name||"Barang"),1)]),t("td",Xt,n(d.qty_received),1),t("td",Zt,n(g(k(d))),1),t("td",te,n(G(d)),1),t("td",ee,n(g(K(d))),1),t("td",oe,n(g(U(d))),1)])}),128))])]),e[59]||(e[59]=t("div",{class:"divider-solid"},null,-1)),t("table",ne,[t("tbody",null,[t("tr",null,[t("td",se,[t("div",ie," * Ket.: "+n(a.goodsReceipt.notes||"MO CASH KRM PARLIN PAGARUYUNG HANGTUAH DURI"),1),t("div",le," * sudah termasuk PPN      "+n(E.value)+" ("+n(lt(S)(a.goodsReceipt.created_at||new Date).format("HH:mm:ss"))+")    Via : SLS ",1),t("div",re,[t("div",de,[C(L,{value:it.value,size:34,level:"M","render-as":"svg"},null,8,["value"])]),e[41]||(e[41]=t("div",{style:{"font-size":"7.5px","line-height":"1.2",color:"#333"}},[t("strong",null,"VERIFIKASI DATA RESMI"),t("br"),h(" Scan QR untuk validasi keabsahan data faktur pada sistem Ms.POS ")],-1))]),t("table",ae,[t("tbody",null,[t("tr",null,[t("td",ue,[t("div",pe,n(M.value),1),e[42]||(e[42]=t("div",{style:{height:"36px",display:"flex","align-items":"center","justify-content":"center"}},[t("span",{style:{"font-size":"8px",color:"#999"}},"( Cap & TTD Basah )")],-1)),e[43]||(e[43]=t("div",{class:"font-bold"},"( PEKANBARU )",-1))]),t("td",ge,[e[45]||(e[45]=t("div",{class:"font-bold",style:{"font-size":"10px","margin-bottom":"2px"}},"PENERIMA",-1)),t("div",ce,[C(L,{value:V.value,size:34,level:"M","render-as":"svg"},null,8,["value"]),e[44]||(e[44]=t("div",{style:{"font-size":"6px",color:"#16a34a","font-weight":"bold"}},"[TERTANDA DIGITAL]",-1))]),t("div",me,"( "+n(E.value)+" )",1)])])])])]),t("td",fe,[t("table",xe,[t("tbody",null,[t("tr",null,[e[46]||(e[46]=t("td",{style:{"font-size":"10.5px"}},"JUMLAH HARGA JUAL",-1)),e[47]||(e[47]=t("td",{style:{width:"25px","text-align":"right"}},"Rp.",-1)),t("td",be,n(g(B.value)),1)]),t("tr",null,[e[48]||(e[48]=t("td",{style:{"font-size":"10.5px"}},"EXTRA DISCOUNT",-1)),e[49]||(e[49]=t("td",{style:{"text-align":"right"}},"Rp.",-1)),t("td",ye,n(g(a.goodsReceipt.extra_discount||0)),1)]),t("tr",he,[e[50]||(e[50]=t("td",{style:{"font-weight":"bold","font-size":"11px",padding:"3px 0","letter-spacing":"1px"}},"T O T A L (Inc Ppn)",-1)),e[51]||(e[51]=t("td",{style:{"text-align":"right","font-weight":"bold",padding:"3px 0"}},"Rp.",-1)),t("td",ve,n(g(T.value)),1)]),t("tr",null,[e[52]||(e[52]=t("td",{style:{"font-size":"10px","padding-top":"4px","letter-spacing":"1px"}},"D P P",-1)),e[53]||(e[53]=t("td",{style:{"text-align":"right","padding-top":"4px"}},"Rp.",-1)),t("td",Re,n(g(O.value)),1)]),t("tr",null,[e[54]||(e[54]=t("td",{style:{"font-size":"10px","letter-spacing":"1px"}},"D P P LAIN",-1)),e[55]||(e[55]=t("td",{style:{"text-align":"right"}},"Rp.",-1)),t("td",Ne,n(g(X.value)),1)]),t("tr",null,[e[56]||(e[56]=t("td",{style:{"font-size":"10px","letter-spacing":"1px"}},"P P N",-1)),e[57]||(e[57]=t("td",{style:{"text-align":"right"}},"Rp.",-1)),t("td",_e,n(g($.value)),1)])])])])])])])]))])):D("",!0)}}};export{Ie as default};
