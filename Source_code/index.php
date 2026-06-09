
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Paynest — Invoicing, Receipts, Payment Tracking & Business Management Platform</title>
<meta name="description" content="Paynest is an invoicing and receipt system that helps businesses create invoices, record payments, track revenue, manage transport operations and generate reports, all in one platform designed to grow into a complete business management and ERP solution.">
<meta name="keywords" content="invoicing system, receipt system, payment tracking software, revenue tracking, transport management software, SaaS business software, ERP platform, analytics system">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://paynest.co.ke/">
<meta property="og:title" content="Paynest — Business Invoicing & Revenue Management Platform">
<meta property="og:description" content="Create invoices, manage payments, track revenue and run your business operations with Paynest.">
<meta property="og:url" content="https://paynest.co.ke">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Paynest — Invoicing & Business Management Platform">
<meta name="twitter:description" content="Smart invoicing, payment tracking and analytics for modern businesses.">
<style>
:root{
  --orange:#ff7a00;
  --orange-dark:#e86f00;
}
*{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto}
body{
  margin:0;
  min-height:100vh;
  background:linear-gradient(135deg,var(--orange),var(--orange-dark));
  color:#fff;
  overflow-x:hidden;
}
header{
  display:flex;
  justify-content:flex-end;
  align-items:center;
  padding:18px 28px;
}
.top-slider{
  position:relative;
  height:100vh;
  height:100dvh;
  width:100%;
  overflow:hidden;
  background:#000;
}
@media (max-width:768px){
  .top-slider{
    min-height:105dvh;
  }
}
.hero-bg{
  position:absolute;
  inset:0;
}
.slide{
  position:absolute;
  inset:0;
  opacity:0;
  transition:opacity 1s ease;
}
.slide.active{opacity:1}
.slide img{
  width:100%;
  height:100%;
  object-fit:cover;
  object-position:center;
  display:block;
  transform:scale(1);
  transition:transform 10s ease;
}
.slide.active img{
  transform:scale(1.06);
}
@media (max-width:768px){
  .slide img{
    object-fit:contain;
    background:#000;
  }
}
.slider-progress{
  position:absolute;
  bottom:0;
  left:0;
  height:4px;
  width:100%;
  background:rgba(255,255,255,.2);
}
.slider-progress-bar{
  height:100%;
  width:0%;
  background:#fff;
}
.btn{
  background:#fff;
  color:var(--orange);
  padding:10px 16px;
  border-radius:8px;
  font-weight:600;
  text-decoration:none;
  margin-left:10px;
}
.hero{
  max-width:1100px;
  margin:80px auto;
  padding:0 20px;
  text-align:center;
}
.hero h1{font-size:42px;margin-bottom:16px}
.hero p{font-size:18px;opacity:.95}
.features{
  margin-top:50px;
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
  gap:20px;
}
.card{
  background:rgba(255,255,255,.15);
  padding:22px;
  border-radius:14px;
}
.section{
  max-width:1100px;
  margin:80px auto;
  padding:0 20px;
}
.grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
  gap:20px;
}
.panel{
  background:rgba(255,255,255,.1);
  padding:20px;
  border-radius:12px;
}
.cta{text-align:center;margin:100px 0}
.cta a{
  background:#fff;
  color:var(--orange);
  padding:14px 22px;
  border-radius:10px;
  font-weight:700;
  text-decoration:none;
}
</style>
<script>
document.addEventListener('keydown',function(e){
  if(e.ctrlKey && e.shiftKey && e.key==='H'){
    document.getElementById('adminLogin').style.display='inline-block';
  }
});
</script>
</head>
<body>
<header>
  <strong style="margin-right:auto">Paynest Systems</strong>
  <div>
    <a href="login.php" class="btn">Login</a>
    <a href="admin_login.php" id="adminLogin" class="btn" style="display:none">Admin Login</a>
  </div>
</header>
<section class="top-slider" id="slider">
  <div class="hero-bg" id="heroBg"></div>
  <div class="slider-progress"><div class="slider-progress-bar" id="progressBar"></div></div>
</section>
<section class="hero">
  <h1 id="dynamicTitle"></h1>
  <p id="dynamicMessage"></p>
  <div class="features">
    <div class="card">
      <h3>💳 Payments</h3>
      <p>M-Pesa STK Push, Paybill & Till with automatic reconciliation.</p>
    </div>
    <div class="card">
      <h3>📊 Analytics</h3>
      <p>Track sales, subscriptions, routes, vehicles and profits.</p>
    </div>
    <div class="card">
      <h3>🔐 Secure</h3>
      <p>Role-based access, encrypted passwords, and audit logs.</p>
    </div>
  </div>
</section>
<section class="section">
  <h2>Everything Your Business Needs</h2>
  <div class="grid">
    <div class="panel">Create invoices and receipts instantly.</div>
    <div class="panel">Automate payment tracking and reconciliation.</div>
    <div class="panel">Manage transport operations and routes.</div>
    <div class="panel">Generate powerful reports and insights.</div>
  </div>
</section>
<section class="cta">
  <h2>Run your operations smarter with Paynest</h2>
  <a href="login.php">Get Started</a>
</section>
<script>
const imageList = <?php
$files = glob(__DIR__ . '/assets/*.png');
$urls = array_map(function($f){
  return 'assets/' . basename($f);
}, $files);
shuffle($urls);
echo json_encode(array_values($urls));
?>;
const heroBg=document.getElementById('heroBg');
const progressBar=document.getElementById('progressBar');
const slider=document.getElementById('slider');
let slides=[];
let index=0;
let interval=5000;
let timer;
function preload(list){
  return Promise.all(list.map(src=>{
    return new Promise(resolve=>{
      const img=new Image();
      img.src=src;
      img.onload=resolve;
      img.onerror=resolve;
    });
  }));
}
function build(){
  imageList.forEach((src,i)=>{
    const div=document.createElement('div');
    div.className='slide'+(i===0?' active':'');
    const img=document.createElement('img');
    img.src=src;
    div.appendChild(img);
    heroBg.appendChild(div);
  });
  slides=document.querySelectorAll('.slide');
  start();
}
function start(){
  progressBar.style.transition='none';
  progressBar.style.width='0%';
  setTimeout(()=>{
    progressBar.style.transition='width '+interval+'ms linear';
    progressBar.style.width='100%';
  },50);
  timer=setTimeout(next,interval);
}
function next(){
  slides[index].classList.remove('active');
  index=(index+1)%slides.length;
  slides[index].classList.add('active');
  start();
}
slider.addEventListener('mouseenter',()=>{
  clearTimeout(timer);
  progressBar.style.transition='none';
});
slider.addEventListener('mouseleave',()=>{
  start();
});
let startX=0;
slider.addEventListener('touchstart',e=>startX=e.touches[0].clientX);
slider.addEventListener('touchend',e=>{
  let diff=e.changedTouches[0].clientX-startX;
  if(Math.abs(diff)>50){
    clearTimeout(timer);
    if(diff<0) next();
    else{
      slides[index].classList.remove('active');
      index=(index-1+slides.length)%slides.length;
      slides[index].classList.add('active');
      start();
    }
  }
});
preload(imageList).then(build);
</script>
<script>
async function loadGeneralInfo(){
  try{
    const res=await fetch('get_general_info.php?ts='+Date.now());
    const data=await res.json();
    document.getElementById('dynamicTitle').textContent=data.title||'';
    document.getElementById('dynamicMessage').innerHTML=data.message||'';
  }catch(e){console.error(e);}
}
loadGeneralInfo();
setInterval(loadGeneralInfo,60000);
</script>
</body>
</html>
