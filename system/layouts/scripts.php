<!-- Vendor js -->
<script src="assets/js/vendor.min.js?v=<?= $crm_version ?>"></script>



<script src="assets/vendor/jquery-toast-plugin/jquery.toast.min.js?v=<?= $crm_version ?>"></script>

<!-- Bootstrap Touchspin js -->
<script src="assets/vendor/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js?v=<?= $crm_version ?>"></script>

<!-- Code Highlight js -->
<script src="assets/vendor/highlightjs/highlight.pack.min.js?v=<?= $crm_version ?>"></script>
<script src="assets/vendor/clipboard/clipboard.min.js?v=<?= $crm_version ?>"></script>
<script src="assets/js/hyper-syntax.js?v=<?= $crm_version ?>"></script>

<!-- Datatable js -->
<script src="assets/vendor/datatables.net/js/jquery.dataTables.min.js?v=<?= $crm_version ?>"></script>
<script src="assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js?v=<?= $crm_version ?>"></script>
<script src="assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js?v=<?= $crm_version ?>"></script>
<script src="assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js?v=<?= $crm_version ?>"></script>
<script src="assets/vendor/jquery-datatables-checkboxes/js/dataTables.checkboxes.min.js?v=<?= $crm_version ?>"></script>



<?php if($page == 'customers'): ?>
    <!-- Customers App js -->
    <script src="assets/js/pages/customers.js?v=<?= $crm_version ?>"></script>

    <?php elseif($page == 'finance'): ?>

    <!-- Centers App js -->
    <script src="assets/js/pages/cashes.js?v=<?= $crm_version ?>"></script>
    <script src="assets/js/pages/transactions.js?v=<?= $crm_version ?>"></script>
    <script src="assets/js/pages/suppliers.js?v=<?= $crm_version ?>"></script>


    <?php elseif($page == 'settings-centers'): ?>

    <!-- Centers App js -->
    <script src="assets/js/pages/settings-centers.js?v=<?= $crm_version ?>"></script>

    <?php elseif($page == 'settings-countries'): ?>

    <!-- Centers App js -->
    <script src="assets/js/pages/settings-countries.js?v=<?= $crm_version ?>"></script>


    <?php elseif($page == 'settings-cities'): ?>

    <!-- Centers App js -->
    <script src="assets/js/pages/settings-cities.js?v=<?= $crm_version ?>"></script>



    <?php elseif($page == 'settings-inputs'): ?>

    <!-- Centers App js -->
    <script src="assets/js/pages/settings-inputs.js?v=<?= $crm_version ?>"></script>

    <?php elseif($page == 'clients'): ?>

    <!-- Clients App js -->
    <script src="assets/js/pages/clients.js?v=<?= $crm_version ?>"></script>

    <?php endif; ?>




<!-- Input Mask Plugin js -->
<script src="assets/vendor/jquery-mask-plugin/jquery.mask.min.js?v=<?= $crm_version ?>"></script>

<!-- Bootstrap Maxlength js -->
<script src="assets/vendor/bootstrap-maxlength/bootstrap-maxlength.min.js?v=<?= $crm_version ?>"></script>


<!-- Code Highlight js -->
<!-- <script src="assets/vendor/highlightjs/highlight.pack.min.js"></script>
<script src="assets/vendor/clipboard/clipboard.min.js"></script>
<script src="assets/js/hyper-syntax.js"></script> -->

<!-- Input Mask Plugin js -->
<script src="assets/vendor/jquery-mask-plugin/jquery.mask.min.js?v=<?= $crm_version ?>"></script>

<!-- Bootstrap Maxlength js -->
<script src="assets/vendor/bootstrap-maxlength/bootstrap-maxlength.min.js?v=<?= $crm_version ?>"></script>

<!--  Select2 Js -->
<script src="assets/vendor/select2/js/select2.min.js?v=<?= $crm_version ?>"></script>

<!-- Daterangepicker js -->
<script src="assets/vendor/moment/min/moment.min.js?v=<?= $crm_version ?>"></script>
<script src="assets/vendor/moment/locale/ru.js?v=<?= $crm_version ?>"></script>
<script src="assets/vendor/daterangepicker/daterangepicker.js?v=<?= $crm_version ?>"></script>

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
<script src="assets/js/app.js?v=<?= $crm_version ?>"></script>

<script>
// Глобальное исправление для "залипания" подсветки в Select2 при прокрутке колесиком
(function() {
    let scrollTimeout;
    $(document).on('wheel', '.select2-results__options', function(e) {
        const optionsList = $(this);
        
        // Добавляем класс, который отключает hover-эффекты через CSS
        if (!optionsList.hasClass('scrolling-with-wheel')) {
            optionsList.addClass('scrolling-with-wheel');
        }
        
        // Очищаем старый таймер, если он был
        clearTimeout(scrollTimeout);
        
        // Устанавливаем новый таймер. Если прокрутки не было 150мс, считаем, что она закончилась.
        scrollTimeout = setTimeout(function() {
            optionsList.removeClass('scrolling-with-wheel');
        }, 150);
    });
})();
</script>