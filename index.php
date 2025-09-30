<?php require_once('config.php'); ?>
<?php 
if(isset($_SESSION['msg_status'])){
   $msg_status = $_SESSION['msg_status'];
   unset($_SESSION['msg_status']);
}
if($_SERVER['REQUEST_METHOD'] == "POST"){
   $data = '';
   foreach($_POST as $k => $v){
      if(!empty($data)) $data .= " , ";
      $data .= " `{$k}` = '{$v}' ";
   }
   $sql  = "INSERT INTO `messages` set {$data}";
   $save = $conn->query($sql);
   if($save){
      $msg_status = "success";
      foreach($_POST as $k => $v){
         unset($_POST[$k]);
      }
      $_SESSION['msg_status'] = $msg_status;
      header('location:'.$_SERVER['HTTP_REFERER']);
   }else{
      $msg_status = "failed";
      echo "<script>console.log('".$conn->error."')</script>";
      echo "<script>console.log('Query','".$sql."')</script>";
   }
}

?>
 <!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
<?php require_once('inc/header.php') ?>
<style>
   #about h2 {
      color: #2b2b2b;
   }
</style>
  <body>

   <!-- Header
   ================================================== -->
   <header id="home" style="background: #161415 url(<?php echo validate_image($_settings->info('banner')) ?>) no-repeat top center;">

      <nav id="nav-wrap">

         <a class="mobile-btn" href="#nav-wrap" title="Show navigation">Show navigation</a>
         <a class="mobile-btn" href="#" title="Hide navigation">Hide navigation</a>

         <ul id="nav" class="nav">
            <li class="current"><a class="smoothscroll" href="#home">Beranda</a></li>
            <li><a class="smoothscroll" href="#about">Tentang</a></li>
           <li><a class="smoothscroll" href="#resume">Layanan</a></li>
           <li><a class="smoothscroll" href="#clients">Mitra</a></li>
            <li><a class="smoothscroll" href="#testimonials">Staff</a></li>
            <li><a class="smoothscroll" href="#contact_us">Kontak</a></li>
         </ul> <!-- end #nav -->

      </nav> <!-- end #nav-wrap -->
<?php 
$u_qry = $conn->query("SELECT * FROM users where id = 1");
foreach($u_qry->fetch_array() as $k => $v){
  if(!is_numeric($k)){
    $user[$k] = $v;
  }
}
$c_qry = $conn->query("SELECT * FROM contacts");
while($row = $c_qry->fetch_assoc()){
    $contact[$row['meta_field']] = $row['meta_value'];
}
// var_dump($contact['facebook']);
?>
<?php
// === Helpers untuk Google Maps ===
function build_embed_from_input($raw, $addr=''){
  $raw  = trim((string)$raw);
  $addr = trim(preg_replace('/\s+/', ' ', strip_tags((string)$addr)));

  if ($raw === '') {
    return $addr !== '' ? 'https://www.google.com/maps?q='.rawurlencode($addr).'&output=embed' : '';
  }
  // kalau admin paste <iframe ... src="...">
  if (stripos($raw, '<iframe') !== false && preg_match('/src=["\']([^"\']+)["\']/i', $raw, $m)) {
    return $m[1];
  }
  // kalau sudah embed URL
  if (strpos($raw, '/maps/embed?') !== false) {
    return $raw;
  }
  // share link dengan @lat,lng
  if (preg_match('/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/', $raw, $m)) {
    return 'https://www.google.com/maps?q='.$m[1].','.$m[2].'&z=16&output=embed';
  }
  // share link dengan ?q=
  if (preg_match('/[?&]q=([^&]+)/', $raw, $m)) {
    $q = urldecode($m[1]);
    return 'https://www.google.com/maps?q='.rawurlencode($q).'&output=embed';
  }
  // fallback alamat
  return $addr !== '' ? 'https://www.google.com/maps?q='.rawurlencode($addr).'&output=embed' : '';
}

function build_click_from_input($raw, $addr=''){
  $raw  = trim((string)$raw);
  $addr = trim(preg_replace('/\s+/', ' ', strip_tags((string)$addr)));

  // Jika admin paste <iframe>, ambil src-nya dulu
  if ($raw !== '' && stripos($raw, '<iframe') !== false && preg_match('/src=["\']([^"\']+)["\']/i', $raw, $m)) {
    $raw = $m[1];
  }

  // 1) Share link model .../@<lat>,<lng>...
  if ($raw !== '' && preg_match('/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/', $raw, $mm)) {
    return 'https://www.google.com/maps/search/?api=1&query='.$mm[1].','.$mm[2];
  }

  // 2) URL embed (…/maps/embed?pb=…) – ekstrak koordinat dari pb
  if ($raw !== '' && (strpos($raw, '/maps/embed?') !== false || strpos($raw, 'pb=') !== false)) {
    // Pola !3d<lat>!4d<lng>
    if (preg_match('/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/', $raw, $m34)) {
      $lat = $m34[1]; $lng = $m34[2];
      return 'https://www.google.com/maps/search/?api=1&query='.$lat.','.$lng;
    }
    // Pola !2d<lng>!3d<lat>
    if (preg_match('/!2d(-?\d+(?:\.\d+)?)!3d(-?\d+(?:\.\d+)?)/', $raw, $m23)) {
      $lng = $m23[1]; $lat = $m23[2];
      return 'https://www.google.com/maps/search/?api=1&query='.$lat.','.$lng;
    }
    // Fallback: pakai alamat
    if ($addr !== '') {
      return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($addr);
    }
  }

  // 3) Link biasa (maps.app.goo.gl, /place, dsb) – pakai apa adanya
  if ($raw !== '') return $raw;

  // 4) Fallback terakhir: alamat
  if ($addr !== '') {
    return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($addr);
  }

  return '';
}



// === Data kontak & map yang dipakai di seluruh halaman ===
$tel     = isset($contact['mobile']) ? preg_replace('/\D+/', '', $contact['mobile']) : '';
$email   = trim($contact['email'] ?? '');
$addr    = trim(preg_replace('/\s+/', ' ', strip_tags($contact['address'] ?? '')));
$rawMap  = trim($contact['map_embed'] ?? '');

$embedSrc  = build_embed_from_input($rawMap, $addr);  // untuk <iframe>
$clickLink = build_click_from_input($rawMap, $addr);  // untuk tombol ikon peta
?>

      <div class="row banner">
         <div class="banner-text">
            <h1 class="responsive-headline"><?php echo $_settings->info('name') ?></h1>
            <h3><?php echo stripslashes($_settings->info('welcome_message')) ?></h3>
            <hr />
            

<ul class="social contact-actions">
  <?php if ($tel): ?>
    <li><a href="<?= htmlspecialchars('tel:' . $tel, ENT_QUOTES) ?>" class="btn-icon" title="Telepon" aria-label="Telepon">
      <i class="fa fa-phone"></i></a></li>
  <?php endif; ?>

  <?php if ($email !== ''): ?>
    <li><a href="<?= htmlspecialchars('mailto:' . $email, ENT_QUOTES) ?>" class="btn-icon" title="Email" aria-label="Email">
      <i class="fa fa-envelope"></i></a></li>
  <?php endif; ?>

  <?php if ($clickLink !== ''): ?>
    <li><a href="<?= htmlspecialchars($clickLink, ENT_QUOTES) ?>" target="_blank" rel="noopener"
           class="btn-icon" title="Lihat Lokasi" aria-label="Lihat Lokasi">
      <i class="fa fa-map-marker"></i></a></li>
  <?php endif; ?>
</ul>




         </div>
      </div>

      <p class="scrolldown">
         <a class="smoothscroll" href="#about"><i class="icon-down-circle"></i></a>
      </p>

   </header> <!-- Header End -->


   <!-- About Section
   ================================================== -->
   <section id="about" style="background:#f7f7f7 !important">

      <div class="row">

         <div class="two columns">

            <img class="profile-pic"  src="<?php echo validate_image($_settings->info('logo')) ?>" alt="" />

         </div>

         <div class="ten columns main-col">

            <h2>Tentang Kami</h2>
            <div id="about_me"><?php include "about.html"; ?></div>

            <div class="row">

               <div class="columns contact-details">

                

               </div>

               <div class="columns download">
                  <p>
                     <!-- <a href="#" class="button"><i class="fa fa-download"></i>Download Resume</a> -->
                  </p>
               </div>

            </div> <!-- end row -->

         </div> <!-- end .main-col -->

      </div>

   </section> <!-- About Section End-->


   <!-- Resume Section
   ================================================== -->
   <section id="resume">
      <!-- Education
      ----------------------------------------------- -->
      <div class="row education">

         <div class="two columns header-col">
            <h1><span>Layanan</span></h1>
         </div>

         <div class="ten columns main-col">
          <?php 
          $e_qry = $conn->query("SELECT * FROM services order by title asc");
          while($row = $e_qry->fetch_assoc()):
          ?>
            <div class="row item">

               <div class="twelve columns">

                  <h3><?php echo $row['title'] ?></h3>
                  <hr>
                  <img src="<?php echo validate_image($row['file_path']) ?>" alt="" class="img-fluid service-img-view">
                  <p>
                  <?php echo stripslashes(html_entity_decode($row['description'])) ?>
                  </p>

               </div>

            </div> <!-- item end -->
          <?php endwhile; ?>
           

         </div> <!-- main-col end -->

      </div> <!-- End Education -->
   </section>
   <section id="clients" style="padding-top:5rem; background: #eaeaea;">

      <div class="row">

         <div class="twelve columns collapsed">

            <h1>Mitra Kami</h1>

            <!-- portfolio-wrapper -->
            <div id="portfolio-wrapper" class="bgrid-quarters s-bgrid-thirds cf">
               <?php 
                  $p_qry = $conn->query("SELECT * FROM clients order by company_name asc");
                  
                  while($row = $p_qry->fetch_assoc()):
                  ?>
                 <div class="columns portfolio-item">
                    <div class="item-wrap">

                       <a href="#modal-<?php echo $row['id'] ?>" title="">
                          <img alt="" src="<?php echo validate_image($row['file_path']) ?>">
                          <div class="overlay">
                             <div class="portfolio-item-meta">
                            <h5 class="truncate-1"><?php echo $row['company_name'] ?></h5>
                                <!-- <p>Illustrration</p> -->
                         </div>
                          </div>
                          <div class="link-icon"><i class="fa fa-eye"></i></div>
                       </a>
                    </div>
                </div> <!-- item end -->
              <?php endwhile; ?>

            </div> <!-- portfolio-wrapper end -->

         </div> <!-- twelve columns end -->


          <?php 
              $p_qry = $conn->query("SELECT * FROM clients ");
              while($row = $p_qry->fetch_assoc()):
            ?>

         <!-- Modal Popup
        --------------------------------------------------------------- -->

         <div id="modal-<?php echo $row['id'] ?>" class="popup-modal mfp-hide">

          <img class="img-fluid client-logo-modal" src="<?php echo validate_image($row['file_path']) ?>" alt="" />

          <div class="description-box">
            <h4><?php echo $row['company_name'] ?></h4>
            <p><?php echo stripslashes(html_entity_decode($row['description'])) ?></p>
          </div>

            <div class="link-box">
               <!-- <a href="http://srikrishnacommunication.com/Giridesigns.html" target="_blank">Details</a> -->
             <a class="popup-modal-dismiss">Close</a>
            </div>

        </div><!-- modal-01 End -->

      <?php endwhile; ?>


      </div> <!-- row End -->

   </section> 


   <section id="testimonials">
      <div class="filter-div"></div>
      <div class="text-container">

         <div class="row">

            <div class="two columns header-col">

               <h1><span>Jajaran Staff</span></h1>

            </div>

            <div class="ten columns flex-container">

               <div class="flexslider">

                  <ul class="slides" id='testimonial-quotes'>

                     <?php 
                     $qry = $conn->query("SELECT * FROM testimonials order by RAND() ");
                     while($row=$qry->fetch_assoc()):
                        $row['message'] = html_entity_decode($row['message']);
                     ?>
                     <li>
                        <blockquote>
                           <p><?php echo $row['message'] ?>
                           </p>
                           <div style="display:flex;align-items:center;">
                              <img src="<?php echo validate_image($row['file_path']) ?>" class="testimonials-avatar" alt="">
                              <cite><?php echo $row['message_from'] ?></cite>
                           </div>
                        </blockquote>
                     </li> <!-- slide ends -->
                     <?php endwhile; ?>


                  </ul>

               </div> <!-- div.flexslider ends -->

            </div> <!-- div.flex-container ends -->

         </div> <!-- row ends -->

       </div>  <!-- text-container ends -->
</section> <!-- end #testimonials -->


   <section id="contact_us" class="contact-section">
  <div class="contact-container">
    <h2 class="contact-title">Kontak & Lokasi</h2>

    <div class="contact-grid">
      <div class="contact-card">
        <h3>Informasi Kontak</h3>
        <p><strong>Phone:</strong> <a href="tel:<?php echo $contact['mobile'] ?>"><?php echo $contact['mobile'] ?></a></p>
        <p><strong>Email:</strong> <a href="mailto:<?php echo $contact['email'] ?>"><?php echo $contact['email'] ?></a></p>
        <p><?php echo $contact['address'] ?></p>
      </div>

      <div class="map-card">
        <div class="map-responsive">
          <?php if (!empty($embedSrc)): ?>
            <iframe
              src="<?php echo htmlspecialchars($embedSrc, ENT_QUOTES); ?>"
              allowfullscreen
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade">
            </iframe>
          <?php else: ?>
            <div class="text-center p-4">Peta belum disetel.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>


      <?php require_once('inc/footer.php') ?>
  </body>
</html>
