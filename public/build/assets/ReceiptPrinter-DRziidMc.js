import{d as U}from"./dayjs.min-CJ2xeL79.js";import{Y as C,o as d,c as a,d as t,t as l,F as w,i as c,s as j,h as x,v as Y}from"./main-TWTCmj-z.js";import"./_commonjsHelpers-D6-XlEtG.js";const G={key:0,class:"dotmatrix-wrapper"},H={class:"dotmatrix-header"},q={class:"dotmatrix-store"},W={class:"dotmatrix-store-name"},V={class:"dotmatrix-store-sub"},J={class:"dotmatrix-store-sub"},$={class:"dotmatrix-meta"},Q={class:"font-bold"},X={class:"dotmatrix-table"},Z={style:{"text-align":"center"}},_={class:"font-bold"},tt={key:0,style:{"font-size":"10px"}},it={style:{"text-align":"center"}},lt={style:{"text-align":"right"}},nt={style:{"text-align":"right"}},et={style:{"text-align":"right","font-weight":"bold"}},st={class:"dotmatrix-bottom"},ot={class:"dotmatrix-bottom-left"},dt={class:"dotmatrix-terbilang"},at={class:"dotmatrix-signatures"},rt={class:"dotmatrix-sig-box"},ut={class:"dotmatrix-sig-line"},mt={class:"dotmatrix-bottom-right"},xt={class:"dotmatrix-totals-table"},bt={style:{"text-align":"right"}},gt={key:0},ft={style:{"text-align":"right"}},ht={key:1},pt={style:{"text-align":"right"}},yt={class:"dotmatrix-grand-total"},wt={style:{"text-align":"right"}},ct={style:{"text-align":"right"}},vt={style:{"text-align":"right"}},kt={key:1,class:"thermal-receipt-content"},At={class:"text-center mb-3"},zt={key:0,class:"font-weight-bold mb-1",style:{"font-size":"16px"}},Tt={key:1,class:"mb-1",style:{"font-size":"14px","font-weight":"normal"}},Nt={style:{"font-size":"11px","line-height":"1.2","margin-bottom":"0"}},Mt={key:2,style:{"font-size":"11px","margin-bottom":"0"}},Kt={style:{width:"100%","font-size":"11px","line-height":"1.3"},class:"mb-2"},Pt={style:{width:"100%","font-size":"11px","line-height":"1.3"},class:"mb-2"},Dt={colspan:"3",class:"pb-1"},Ft={style:{width:"45%"}},Ot={key:0,style:{width:"15%"}},Lt={key:1,style:{width:"15%"}},Rt={style:{width:"40%","text-align":"right"},class:"pb-1"},St={style:{width:"100%","font-size":"11px","line-height":"1.3"},class:"mb-3"},Et={style:{"text-align":"right"}},It={key:0},Ut={style:{"text-align":"right"}},jt={key:1},Bt={style:{"text-align":"right"}},Ct={style:{"font-weight":"bold","font-size":"12px"}},Yt={style:{"text-align":"right"},class:"pt-1"},Gt={style:{"text-align":"right"},class:"pt-1"},Ht={style:{"text-align":"right"}},qt={key:2,class:"kwitansi-wrapper"},Wt={class:"kwitansi-header"},Vt={class:"kwitansi-company"},Jt=["src"],$t={class:"kwitansi-company-info"},Qt={class:"kwitansi-company-name"},Xt={class:"kwitansi-company-address"},Zt={class:"kwitansi-company-telp"},_t={class:"kwitansi-meta"},ti={class:"kwitansi-received-from"},ii={class:"kwitansi-terbilang-box"},li={style:{"font-style":"italic"}},ni={class:"kwitansi-items-table"},ei={style:{"text-align":"center"}},si={style:{"text-align":"right"}},oi={class:"kwitansi-footer"},di={class:"kwitansi-footer-left"},ai={key:0},ri={class:"kwitansi-footer-right"},ui={class:"kwitansi-total-box"},mi={style:{"font-weight":"bold"}},xi={class:"kwitansi-signature"},bi={style:{"margin-bottom":"50px"}},gi={style:{"font-weight":"bold","text-decoration":"underline"}},wi={__name:"ReceiptPrinter",props:{sale:{type:Object,required:!1,default:null},branch:{type:Object,required:!1,default:null},cashierName:{type:String,required:!1,default:""},setting:{type:Object,required:!1,default:null},printFormat:{type:String,default:"continuous_form"}},setup(n,{expose:B}){const m=n,s=e=>new Intl.NumberFormat("id-ID").format(e||0),v=e=>e?U(e).format("DD/MM/YYYY HH:mm"):"",k=e=>e?U(e).format("DD MMMM YYYY"):"",r=e=>{const i=["","satu","dua","tiga","empat","lima","enam","tujuh","delapan","sembilan","sepuluh","sebelas"];let u="";return e=parseInt(e),e===0?"nol":(e<12?u=" "+i[e]:e<20?u=r(e-10)+" belas":e<100?u=r(Math.floor(e/10))+" puluh"+r(e%10):e<200?u=" seratus"+r(e-100):e<1e3?u=r(Math.floor(e/100))+" ratus"+r(e%100):e<2e3?u=" seribu"+r(e-1e3):e<1e6?u=r(Math.floor(e/1e3))+" ribu"+r(e%1e3):e<1e9?u=r(Math.floor(e/1e6))+" juta"+r(e%1e6):e<1e12?u=r(Math.floor(e/1e9))+" miliar"+r(e%1e9):e<1e15&&(u=r(Math.floor(e/1e12))+" triliun"+r(e%1e12)),u.trim())},A=C(()=>{if(!m.sale)return"";let e=r(m.sale.grand_total);return e.charAt(0).toUpperCase()+e.slice(1)+" rupiah"});return B({print:()=>{var h;const e=document.getElementById("print-receipt-section");if(!e){window.print();return}const i=document.createElement("iframe");i.style.position="fixed",i.style.right="0",i.style.bottom="0",i.style.width="0",i.style.height="0",i.style.border="0",document.body.appendChild(i);const u=m.printFormat==="continuous_form",y=m.printFormat==="thermal";m.printFormat;let g="";u?g=`
      @page { size: auto; margin: 4mm 6mm; }
      body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 11px;
        line-height: 1.25;
        width: 100%;
        max-width: 241mm;
        color: #000;
        background: #fff;
        padding: 4mm;
      }
      .dotmatrix-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 5px; }
      .dotmatrix-store-name { font-size: 14px; font-weight: bold; }
      .dotmatrix-store-sub { font-size: 10px; }
      .dotmatrix-title-box { text-align: center; }
      .dotmatrix-doc-title { font-size: 15px; font-weight: bold; text-decoration: underline; letter-spacing: 1px; }
      .dotmatrix-doc-sub { font-size: 10px; }
      .dotmatrix-meta table { font-size: 10px; border-collapse: collapse; }
      .dotmatrix-meta td { padding: 1px 3px; }
      .dotmatrix-divider-solid { border-top: 1px solid #000; margin: 4px 0; }
      .dotmatrix-divider-dashed { border-top: 1px dashed #000; margin: 4px 0; }
      .dotmatrix-table { width: 100%; border-collapse: collapse; font-size: 11px; }
      .dotmatrix-table th { border-bottom: 1px solid #000; border-top: 1px solid #000; padding: 3px 4px; font-weight: bold; }
      .dotmatrix-table td { padding: 2px 4px; }
      .dotmatrix-bottom { display: flex; justify-content: space-between; margin-top: 4px; }
      .dotmatrix-bottom-left { width: 55%; }
      .dotmatrix-terbilang { font-size: 10px; margin-bottom: 8px; font-style: italic; }
      .dotmatrix-signatures { display: flex; gap: 30px; margin-bottom: 6px; }
      .dotmatrix-sig-box { text-align: center; font-size: 10px; }
      .dotmatrix-sig-line { margin-top: 35px; font-weight: bold; }
      .dotmatrix-notice { font-size: 9px; font-style: italic; }
      .dotmatrix-bottom-right { width: 40%; }
      .dotmatrix-totals-table { width: 100%; font-size: 11px; border-collapse: collapse; }
      .dotmatrix-totals-table td { padding: 1px 4px; }
      .dotmatrix-grand-total { font-weight: bold; font-size: 12px; border-top: 1px dashed #000; border-bottom: 1px dashed #000; }
      .font-bold { font-weight: bold; }
    `:y?g=`
      @page { size: auto; margin: 0; }
      body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 11px;
        line-height: 1.2;
        width: 58mm;
        color: #000;
        background: #fff;
        padding: 2mm;
      }
      .text-center { text-align: center; }
      .font-weight-bold { font-weight: bold; }
      .divider-dashed { border-top: 1px dashed #000; }
      .mb-1 { margin-bottom: 3px; }
      .mb-2 { margin-bottom: 6px; }
      .mb-3 { margin-bottom: 8px; }
      .mt-3 { margin-top: 8px; }
      .pb-1 { padding-bottom: 3px; }
      .pt-1 { padding-top: 3px; }
      table { width: 100%; border-collapse: collapse; font-size: 11px; }
      td { padding: 1px 2px; }
    `:g=`
      @page { size: auto; margin: 10mm; }
      body {
        font-family: Arial, sans-serif;
        color: #000;
        background: #fff;
        font-size: 12px;
        padding: 5mm;
      }
      .kwitansi-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
      .kwitansi-company { display: flex; align-items: center; gap: 10px; }
      .kwitansi-company-name { font-size: 18px; font-weight: bold; }
      .kwitansi-title { font-size: 22px; font-weight: bold; text-decoration: underline; letter-spacing: 2px; }
      .kwitansi-dashed-line { border-top: 1px dashed #000; margin: 10px 0; }
      .kwitansi-received-from table { width: 100%; font-size: 13px; margin-bottom: 10px; }
      .kwitansi-terbilang-box { background-color: #f0f0f0; border: 1px solid #ccc; padding: 6px 10px; font-size: 12px; margin-bottom: 12px; }
      .kwitansi-items-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 12px; }
      .kwitansi-items-table th, .kwitansi-items-table td { border: 1px solid #000; padding: 5px 8px; }
      .kwitansi-items-table th { background-color: #f0f0f0; }
      .kwitansi-footer { display: flex; justify-content: space-between; }
      .kwitansi-footer-left { width: 50%; }
      .kwitansi-footer-right { width: 40%; text-align: right; }
      .kwitansi-total-box { border: 2px solid #000; padding: 6px 10px; font-size: 14px; display: flex; justify-content: space-between; margin-bottom: 15px; }
      .kwitansi-signature { text-align: center; font-size: 12px; margin-top: 10px; }
    `;const f=i.contentWindow.document;f.open(),f.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Faktur / Struk - ${((h=m.sale)==null?void 0:h.invoice_number)||"Receipt"}</title>
      <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        ${g}
      </style>
    </head>
    <body>
      ${e.innerHTML}
    </body>
    </html>
  `),f.close(),setTimeout(()=>{i.contentWindow.focus(),i.contentWindow.print(),setTimeout(()=>{document.body.removeChild(i)},1500)},250)}}),(e,i)=>{var u,y,g,f,h,z,T,N,M,K,P,D,F,O,L,R,S,E;return n.sale?(d(),a("div",{key:0,id:"print-receipt-section",class:Y({"format-continuous-form":m.printFormat==="continuous_form","format-thermal":m.printFormat==="thermal","format-kwitansi":m.printFormat==="kwitansi"})},[m.printFormat==="continuous_form"?(d(),a("div",G,[t("div",H,[t("div",q,[t("div",W,l(((y=(u=n.branch)==null?void 0:u.owner)==null?void 0:y.name)||((g=n.branch)==null?void 0:g.name)||"PT. DUMAI INVENTORI"),1),t("div",V,l(((f=n.branch)==null?void 0:f.address)||"Alamat Toko"),1),t("div",J,"TELP: "+l(((h=n.branch)==null?void 0:h.contact)||"-"),1)]),i[8]||(i[8]=t("div",{class:"dotmatrix-title-box"},[t("div",{class:"dotmatrix-doc-title"},"FAKTUR PENJUALAN"),t("div",{class:"dotmatrix-doc-sub"},"(NOTA TOKO)")],-1)),t("div",$,[t("table",null,[t("tbody",null,[t("tr",null,[i[0]||(i[0]=t("td",null,"NO. FAKTUR",-1)),i[1]||(i[1]=t("td",null,":",-1)),t("td",Q,l(n.sale.invoice_number),1)]),t("tr",null,[i[2]||(i[2]=t("td",null,"TANGGAL",-1)),i[3]||(i[3]=t("td",null,":",-1)),t("td",null,l(v(n.sale.transaction_date)),1)]),t("tr",null,[i[4]||(i[4]=t("td",null,"KASIR",-1)),i[5]||(i[5]=t("td",null,":",-1)),t("td",null,l(n.cashierName||"KASIR"),1)]),t("tr",null,[i[6]||(i[6]=t("td",null,"PELANGGAN",-1)),i[7]||(i[7]=t("td",null,":",-1)),t("td",null,l(((z=n.sale.customer)==null?void 0:z.name)||"UMUM"),1)])])])])]),i[26]||(i[26]=t("div",{class:"dotmatrix-divider-solid"},null,-1)),t("table",X,[i[9]||(i[9]=t("thead",null,[t("tr",null,[t("th",{style:{width:"30px","text-align":"center"}},"NO"),t("th",{style:{"text-align":"left"}},"KODE / NAMA BARANG"),t("th",{style:{width:"80px","text-align":"center"}},"QTY"),t("th",{style:{width:"100px","text-align":"right"}},"HARGA"),t("th",{style:{width:"80px","text-align":"right"}},"DISC"),t("th",{style:{width:"120px","text-align":"right"}},"JUMLAH")])],-1)),t("tbody",null,[(d(!0),a(w,null,c(n.sale.items,(o,b)=>{var p,I;return d(),a("tr",{key:b},[t("td",Z,l(b+1),1),t("td",null,[t("span",_,l(((p=o.product)==null?void 0:p.name)||o.product_name),1),(I=o.product)!=null&&I.sku?(d(),a("span",tt," ["+l(o.product.sku)+"]",1)):x("",!0)]),t("td",it,l(o.quantity)+" "+l(o.unit||"pcs"),1),t("td",lt,l(s(o.unit_price)),1),t("td",nt,l(o.discount>0?s(o.discount):"-"),1),t("td",et,l(s(o.subtotal)),1)])}),128))])]),i[27]||(i[27]=t("div",{class:"dotmatrix-divider-dashed"},null,-1)),t("div",st,[t("div",ot,[t("div",dt,[i[10]||(i[10]=j(" Terbilang: ")),t("em",null,"# "+l(A.value)+" #",1)]),t("div",at,[i[12]||(i[12]=t("div",{class:"dotmatrix-sig-box"},[t("div",null,"Tanda Terima,"),t("div",{class:"dotmatrix-sig-line"},"( Pelanggan )")],-1)),t("div",rt,[i[11]||(i[11]=t("div",null,"Hormat Kami,",-1)),t("div",ut,"( "+l(n.cashierName||"Kasir")+" )",1)])]),i[13]||(i[13]=t("div",{class:"dotmatrix-notice"}," * Perhatian: Barang yang sudah dibeli tidak dapat ditukar/dikembalikan. ",-1))]),t("div",mt,[t("table",xt,[t("tbody",null,[t("tr",null,[i[14]||(i[14]=t("td",null,"SUBTOTAL",-1)),i[15]||(i[15]=t("td",null,":",-1)),t("td",bt,l(s(n.sale.total_amount)),1)]),n.sale.discount>0?(d(),a("tr",gt,[i[16]||(i[16]=t("td",null,"DISKON",-1)),i[17]||(i[17]=t("td",null,":",-1)),t("td",ft,"-"+l(s(n.sale.discount)),1)])):x("",!0),n.sale.tax_amount>0?(d(),a("tr",ht,[i[18]||(i[18]=t("td",null,"PPN",-1)),i[19]||(i[19]=t("td",null,":",-1)),t("td",pt,l(s(n.sale.tax_amount)),1)])):x("",!0),t("tr",yt,[i[20]||(i[20]=t("td",null,"GRAND TOTAL",-1)),i[21]||(i[21]=t("td",null,":",-1)),t("td",wt,"Rp "+l(s(n.sale.grand_total)),1)]),t("tr",null,[i[22]||(i[22]=t("td",null,"BAYAR (TUNAI)",-1)),i[23]||(i[23]=t("td",null,":",-1)),t("td",ct,"Rp "+l(s(n.sale.paid_amount||n.sale.grand_total)),1)]),t("tr",null,[i[24]||(i[24]=t("td",null,"KEMBALI",-1)),i[25]||(i[25]=t("td",null,":",-1)),t("td",vt,"Rp "+l(s(n.sale.change_amount||0)),1)])])])])])])):m.printFormat==="thermal"?(d(),a("div",kt,[t("div",At,[(T=n.branch)!=null&&T.owner?(d(),a("h2",zt,l(n.branch.owner.name),1)):x("",!0),n.branch?(d(),a("h3",Tt,l(n.branch.name),1)):x("",!0),t("p",Nt,l(((N=n.branch)==null?void 0:N.address)||""),1),(M=n.branch)!=null&&M.contact?(d(),a("p",Mt," Telp: "+l(n.branch.contact),1)):x("",!0)]),i[42]||(i[42]=t("div",{class:"divider-dashed mb-2"},null,-1)),t("table",Kt,[t("tbody",null,[t("tr",null,[i[28]||(i[28]=t("td",{style:{width:"45px"}},"Faktur",-1)),i[29]||(i[29]=t("td",null,":",-1)),t("td",null,l(n.sale.invoice_number),1)]),t("tr",null,[i[30]||(i[30]=t("td",null,"Waktu",-1)),i[31]||(i[31]=t("td",null,":",-1)),t("td",null,l(v(n.sale.transaction_date)),1)]),t("tr",null,[i[32]||(i[32]=t("td",null,"Kasir",-1)),i[33]||(i[33]=t("td",null,":",-1)),t("td",null,l(n.cashierName||"Kasir"),1)]),t("tr",null,[i[34]||(i[34]=t("td",null,"Plgn",-1)),i[35]||(i[35]=t("td",null,":",-1)),t("td",null,l(((K=n.sale.customer)==null?void 0:K.name)||"Umum"),1)])])]),i[43]||(i[43]=t("div",{class:"divider-dashed mb-2"},null,-1)),t("table",Pt,[t("tbody",null,[(d(!0),a(w,null,c(n.sale.items,o=>{var b;return d(),a(w,{key:o.id},[t("tr",null,[t("td",Dt,l(((b=o.product)==null?void 0:b.name)||o.product_name),1)]),t("tr",null,[t("td",Ft,l(o.quantity)+" x "+l(s(o.unit_price)),1),o.discount>0?(d(),a("td",Ot," -"+l(s(o.discount)),1)):(d(),a("td",Lt)),t("td",Rt,l(s(o.subtotal)),1)])],64)}),128))])]),i[44]||(i[44]=t("div",{class:"divider-dashed mb-2"},null,-1)),t("table",St,[t("tbody",null,[t("tr",null,[i[36]||(i[36]=t("td",null,"Subtotal",-1)),t("td",Et,l(s(n.sale.total_amount)),1)]),n.sale.discount>0?(d(),a("tr",It,[i[37]||(i[37]=t("td",null,"Diskon",-1)),t("td",Ut,"-"+l(s(n.sale.discount)),1)])):x("",!0),n.sale.tax_amount>0?(d(),a("tr",jt,[i[38]||(i[38]=t("td",null,"Pajak",-1)),t("td",Bt,l(s(n.sale.tax_amount)),1)])):x("",!0),t("tr",Ct,[i[39]||(i[39]=t("td",{class:"pt-1"},"Total",-1)),t("td",Yt,l(s(n.sale.grand_total)),1)]),t("tr",null,[i[40]||(i[40]=t("td",{class:"pt-1"},"Tunai / Bayar",-1)),t("td",Gt,l(s(n.sale.paid_amount||n.sale.grand_total)),1)]),t("tr",null,[i[41]||(i[41]=t("td",null,"Kembali",-1)),t("td",Ht,l(s(n.sale.change_amount||0)),1)])])]),i[45]||(i[45]=t("div",{class:"divider-dashed mb-2"},null,-1)),i[46]||(i[46]=t("div",{class:"text-center mt-3",style:{"font-size":"11px"}},[t("p",{class:"mb-1 font-weight-bold"},"Terima Kasih"),t("p",{style:{"font-size":"10px","line-height":"1.1"}}," Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan ")],-1))])):(d(),a("div",qt,[t("div",Wt,[t("div",Vt,[(D=(P=n.branch)==null?void 0:P.owner)!=null&&D.logo?(d(),a("img",{key:0,src:"/storage/"+n.branch.owner.logo,alt:"Logo",class:"kwitansi-logo"},null,8,Jt)):x("",!0),t("div",$t,[t("div",Qt,l(((O=(F=n.branch)==null?void 0:F.owner)==null?void 0:O.name)||"NAMA PERUSAHAAN"),1),t("div",Xt,l(((L=n.branch)==null?void 0:L.address)||"Alamat Perusahaan"),1),t("div",Zt,"TELP. "+l(((R=n.branch)==null?void 0:R.contact)||"-"),1)])]),i[50]||(i[50]=t("div",{class:"kwitansi-title"}," KWITANSI ",-1)),t("div",_t,[t("table",null,[t("tbody",null,[t("tr",null,[i[47]||(i[47]=t("td",null,"Tgl Kwitansi",-1)),t("td",null,": "+l(k(n.sale.transaction_date)),1)]),t("tr",null,[i[48]||(i[48]=t("td",null,"Faktur No",-1)),t("td",null,": "+l(n.sale.invoice_number),1)]),t("tr",null,[i[49]||(i[49]=t("td",null,"No Pelanggan",-1)),t("td",null,": "+l(((S=n.sale.customer)==null?void 0:S.code)||"-"),1)])])])])]),i[61]||(i[61]=t("div",{class:"kwitansi-dashed-line"},null,-1)),t("div",ti,[t("table",null,[t("tbody",null,[t("tr",null,[i[51]||(i[51]=t("td",{style:{width:"140px"}},"Telah terima dari",-1)),t("td",null,": "+l(((E=n.sale.customer)==null?void 0:E.name)||"Pelanggan Umum"),1)]),t("tr",null,[i[52]||(i[52]=t("td",null,"Sejumlah uang",-1)),t("td",null,": "+l(s(n.sale.grand_total)),1)])])]),t("div",ii,[t("span",li,l(A.value),1)])]),t("table",ni,[i[54]||(i[54]=t("thead",null,[t("tr",null,[t("th",{style:{width:"50px"}},"NO"),t("th",null,"K E T E R A N G A N"),t("th",{style:{width:"150px","text-align":"right"}},"JUMLAH")])],-1)),t("tbody",null,[(d(!0),a(w,null,c(n.sale.items,(o,b)=>{var p;return d(),a("tr",{key:b},[t("td",ei,l(b+1),1),t("td",null,[j(l(((p=o.product)==null?void 0:p.name)||o.product_name)+" ",1),i[53]||(i[53]=t("br",null,null,-1)),t("small",null,l(o.quantity)+" x "+l(s(o.unit_price)),1)]),t("td",si,l(s(o.subtotal)),1)])}),128))])]),t("div",oi,[t("div",di,[t("table",null,[t("tbody",null,[t("tr",null,[i[55]||(i[55]=t("td",{style:{width:"100px"}},"Total",-1)),t("td",null,": "+l(s(n.sale.total_amount)),1)]),n.sale.discount>0?(d(),a("tr",ai,[i[56]||(i[56]=t("td",null,"Diskon",-1)),t("td",null,": "+l(s(n.sale.discount)),1)])):x("",!0),t("tr",null,[i[57]||(i[57]=t("td",null,"Grand Total",-1)),t("td",null,": "+l(s(n.sale.grand_total)),1)]),t("tr",null,[i[58]||(i[58]=t("td",null,"Status",-1)),t("td",null,": "+l(n.sale.payment_status==="paid"?"Lunas":"Belum Lunas"),1)])])]),i[59]||(i[59]=t("div",{class:"kwitansi-perhatian-box"},[t("strong",null,"Perhatian :"),t("p",null,"Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan.")],-1))]),t("div",ri,[t("div",ui,[i[60]||(i[60]=t("span",null,"T O T A L :",-1)),t("span",mi,l(s(n.sale.grand_total)),1)]),t("div",xi,[t("div",bi,l(k(new Date)),1),t("div",gi,l(n.cashierName||"Kasir"),1)])])])]))],2)):x("",!0)}}};export{wi as default};
