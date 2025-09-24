 
 <style>

footer a,
footer a:visited {
    color: inherit;
}

footer .copyright {
    color: #d8d8d8;
}
 </style>
 <!-- footer
   ================================================== -->
   <footer>

      <div class="row">

         <div class="twelve columns">

            <?php
// --- siapkan data kontak & link maps aman untuk footer ---
$tel   = isset($contact['mobile']) ? preg_replace('/\D+/', '', $contact['mobile']) : '';
$email = trim($contact['email'] ?? '');
$addr  = trim(preg_replace('/\s+/', ' ', strip_tags($contact['address'] ?? '')));
$raw   = trim($contact['map_embed'] ?? '');   // bisa short link / embed / iframe

// normalkan: kalau admin tempel <iframe>, ambil src-nya
if ($raw !== '' && stripos($raw, '<iframe') !== false && preg_match('/src=["\']([^"\']+)["\']/i', $raw, $m)) {
  $raw = $m[1];
}

// bangun link klik maps (hindari /maps/embed? langsung)
$mapClick = '';
if ($raw !== '') {
  if (strpos($raw, '/maps/embed?') !== false || strpos($raw, 'pb=') !== false) {
    // coba ekstrak lat,lng dari string pb
    if (preg_match('/!3d(-?\d+(?:\.\d+)?)[^!]*!4d(-?\d+(?:\.\d+)?)/', $raw, $m34)) {
      $mapClick = 'https://www.google.com/maps/search/?api=1&query='.$m34[1].','.$m34[2];
    } elseif (preg_match('/!2d(-?\d+(?:\.\d+)?)[^!]*!3d(-?\d+(?:\.\d+)?)/', $raw, $m23)) {
      $mapClick = 'https://www.google.com/maps/search/?api=1&query='.$m23[2].','.$m23[1];
    } else {
      $mapClick = $addr ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($addr) : '';
    }
  } else {
    // short link / share link biasa → pakai apa adanya
    $mapClick = $raw;
  }
} elseif ($addr !== '') {
  $mapClick = 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($addr);
}
?>

<ul class="social-links contact-actions">
  <?php if ($tel): ?>
    <li><a class="btn-icon" href="<?php echo htmlspecialchars('tel:'.$tel, ENT_QUOTES); ?>" title="Telepon"><i class="fa fa-phone"></i></a></li>
  <?php endif; ?>
  <?php if ($email !== ''): ?>
    <li><a class="btn-icon" href="<?php echo htmlspecialchars('mailto:'.$email, ENT_QUOTES); ?>" title="Email"><i class="fa fa-envelope"></i></a></li>
  <?php endif; ?>
  <?php if ($mapClick !== ''): ?>
    <li><a class="btn-icon" href="<?php echo htmlspecialchars($mapClick, ENT_QUOTES); ?>" target="_blank" rel="noopener" title="Lihat Lokasi"><i class="fa fa-map-marker"></i></a></li>
  <?php endif; ?>
</ul>


            <ul class="copyright">
               <li><?php echo $_settings->info('name') ?> &copy; Copyright <?php echo date('Y') ?></li>
              <!--  <li>Design by <a href="http://srikrishnacommunication.com/Giridesigns.html" title="Styleshout" target="_blank">Giri Designs</a></li>    -->
            </ul>

         </div>

         <div id="go-top"><a class="smoothscroll" title="Back to Top" href="#home"><i class="icon-up-open"></i></a></div>

      </div>

   </footer> <!-- Footer End-->

   <!-- Java Script
   ================================================== -->
   <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
   <script>window.jQuery || document.write('<script src="<?php echo base_url ?>profile_asset/js/jquery-1.10.2.min.js"><\/script>')</script>
   <script type="text/javascript" src="<?php echo base_url ?>profile_asset/js/jquery-migrate-1.2.1.min.js"></script>

   <script src="<?php echo base_url ?>profile_asset/js/jquery.flexslider.js"></script>
   <script src="<?php echo base_url ?>profile_asset/js/waypoints.js"></script>
   <script src="<?php echo base_url ?>profile_asset/js/jquery.fittext.js"></script>
   <script src="<?php echo base_url ?>profile_asset/js/magnific-popup.js"></script>
   <script src="<?php echo base_url ?>profile_asset/js/init.js"></script>