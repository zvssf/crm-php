<!-- Vendor js -->
<script src="assets/js/vendor.min.js"></script>



<script src="assets/vendor/jquery-toast-plugin/jquery.toast.min.js"></script>

<!-- Bootstrap Touchspin js -->
<script src="assets/vendor/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js"></script>

<!-- Code Highlight js -->
<script src="assets/vendor/highlightjs/highlight.pack.min.js"></script>
<script src="assets/vendor/clipboard/clipboard.min.js"></script>
<script src="assets/js/hyper-syntax.js"></script>

<!-- Datatable js -->
<script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
<script src="assets/vendor/jquery-datatables-checkboxes/js/dataTables.checkboxes.min.js"></script>



<?php if($page == 'customers'): ?>
    <!-- Customers App js -->
    <script src="assets/js/pages/customers.js"></script>

    <?php elseif($page == 'finance'): ?>

    <!-- Centers App js -->
    <script src="assets/js/pages/cashes.js"></script>
    <script src="assets/js/pages/transactions.js"></script>
    <script src="assets/js/pages/suppliers.js"></script>


    <?php elseif($page == 'settings-centers'): ?>

    <!-- Centers App js -->
    <script src="assets/js/pages/settings-centers.js"></script>

    <?php elseif($page == 'settings-countries'): ?>

    <!-- Centers App js -->
    <script src="assets/js/pages/settings-countries.js"></script>


    <?php elseif($page == 'settings-cities'): ?>

    <!-- Centers App js -->
    <script src="assets/js/pages/settings-cities.js"></script>



    <?php elseif($page == 'settings-inputs'): ?>

    <!-- Centers App js -->
    <script src="assets/js/pages/settings-inputs.js"></script>

    <?php elseif($page == 'clients'): ?>

    <!-- Clients App js -->
    <script src="assets/js/pages/clients.js"></script>

    <?php endif; ?>




<!-- Input Mask Plugin js -->
<script src="assets/vendor/jquery-mask-plugin/jquery.mask.min.js"></script>

<!-- Bootstrap Maxlength js -->
<script src="assets/vendor/bootstrap-maxlength/bootstrap-maxlength.min.js"></script>


<!-- Code Highlight js -->
<!-- <script src="assets/vendor/highlightjs/highlight.pack.min.js"></script>
<script src="assets/vendor/clipboard/clipboard.min.js"></script>
<script src="assets/js/hyper-syntax.js"></script> -->

<!-- Input Mask Plugin js -->
<script src="assets/vendor/jquery-mask-plugin/jquery.mask.min.js"></script>

<!-- Bootstrap Maxlength js -->
<script src="assets/vendor/bootstrap-maxlength/bootstrap-maxlength.min.js"></script>

<!--  Select2 Js -->
<script src="assets/vendor/select2/js/select2.min.js"></script>

<!-- Daterangepicker js -->
<script src="assets/vendor/moment/min/moment.min.js"></script>
<script src="assets/vendor/moment/locale/ru.js"></script>
<script src="assets/vendor/daterangepicker/daterangepicker.js"></script>

<!-- Sweet Alert2 js -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>





<script>
function message(msg_title, msg_text, msg_type, msg_url) {
  if(!msg_url) {
    $.toast({heading: msg_title, text: msg_text, icon: msg_type, position: 'top-right', loader: true, loaderBg: 'rgb(0 0 0 / 20%)'});
  }
  if(msg_url) {
    sessionStorage.setItem('msg_title', msg_title);
    sessionStorage.setItem('msg_text', msg_text);
    sessionStorage.setItem('msg_type', msg_type); // Добавили сохранение типа
    redirect(msg_url);
  }
}
function redirect(url) {
  if (url === 'reload') {
    window.location.reload();
  } else {
    window.location.href = '/?page=' + url;
  }
}
function sessionStorageMSG() {
  if(sessionStorage.getItem('msg_title')) {
    var msg_title = sessionStorage.getItem('msg_title');
    var msg_text = sessionStorage.getItem('msg_text');
    var msg_type = sessionStorage.getItem('msg_type') || 'success'; // Считываем тип
    message(msg_title, msg_text, msg_type, ''); // Используем считанный тип
    sessionStorage.removeItem('msg_title');
    sessionStorage.removeItem('msg_text');
    sessionStorage.removeItem('msg_type'); // Удаляем тип из хранилища
  }
}
$(window).on('load', sessionStorageMSG());
function loaderBTN(btn, status) {
  if(status == 'true') {
    $(btn).attr('disabled', true);
    $(btn + ' .btn-loader, ' + btn + ' .loader-text').removeClass('visually-hidden');
    $(btn + ' .btn-icon, ' + btn + ' .btn-text').addClass('visually-hidden');
  } else if(status == 'false') {
    $(btn).attr('disabled', false);
    $(btn + ' .btn-loader, ' + btn + ' .loader-text').addClass('visually-hidden');
    $(btn + ' .btn-icon, ' + btn + ' .btn-text').removeClass('visually-hidden');
  }
}
</script>





<!-- App js -->
<!-- <script src="assets/js/app.min.js"></script> -->
<script src="assets/js/app.js"></script>
