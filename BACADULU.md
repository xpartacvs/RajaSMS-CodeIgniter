RajaSMS-CodeIgniter
===================

Library codeigniter 2.x untuk layanan sms gateway dari <a href="http://raja-sms.com" target="_blank">RajaSMS</a>

Kebutuhan Sistem
================

<ul>
  <li>PHP >= 5.3</li>
  <li>cURL extension</li>
</ul>

Instalasi
=========

<ol>
  <li>Copy file <code>application/config/rajasms.php</code> ke direktori <code>application/config</code> pada instalasi codeigniter Anda.</li>
  <li>Copy file <code>application/libraries/Rajasms.php</code> ke direktori <code>application/libraries</code> pada instalasi codeigniter Anda.</li>
  <li>Buka file <code>application/config/rajasms.php</code> yang sudah di-<i>copy</i> dengan text editor Anda.</li>
  <li>
    Masukan nilai variabel yang Anda dapat dari akun RajaSMS:<br><br>
    <ul>
      <li><code>$config['rajasms_username'] = 'USERNAME AKUN RAJASMS';</code></li>
      <li><code>$config['rajasms_password'] = 'PASSWORD AKUN RAJASMS';</code></li>
      <li><code>$config['rajasms_key'] = 'APIKEY AKUN RAJASMS';</code></li>
    </ul>
  </li>
</ol>

Daftar Fungsi
=============
<ul>
  <li><code>int get_credit()</code> - Mengambil nilai saldo akun. Return FALSE jika gagal (cek dengan operator <code>===</code>).</li>
  <li><code>string get_expire_date([string $format = 'Y-m-d H:i:s'])</code> - Mengambil tanggal kedaluarsa akun. Return FALSE jika gagal (cek dengan operator <code>===</code>).</li>
  <li><code>int get_expire_timestamp()</code> - Mengambil nilai waktu kedaluarsa akun dalam format UNIX TIMESPAMP. Return FALSE jika gagal (cek dengan operator <code>===</code>).</li>
  <li><code>string get_report(array $sms_result)</code> - Mengambil laporan hasil kirim SMS dari nilai balik fungsi <code>send()</code>. Return FALSE jika gagal (cek dengan operator <code>===</code>).</li>
  <li><code>array send([bool $is_masking = FALSE])</code> - Mengirim SMS. Jika <code>$is_masking</code> bernilai TRUE maka nomor pengirim akan disamarkan. Return FALSE jika gagal (cek dengan operator <code>===</code>).</li>
  <li><code>void set_number(string $nomor_ponsel [,bool $is_validate = FALSE])</code> - Untuk assign nomor ponsel tujuan.</li>
  <li><code>void set_text(string $text)</code> - Untuk assign isi SMS.</li>
  <li><code>void reset()</code> - Menghapus nomor ponsel tujuan dan isi SMS.</li>
  
  
</ul>


Contoh Implementasi pada Controller
===================================

<ol>
  <li>
    Load library:
    <ul>
      <li><code>$this->load->library('rajasms');</code></li>
    </ul>
  </li>
  <li>
    Set nomor ponsel tujuan, set isi SMS, dan mengirim SMS:
    <ul>
      <li><code>$this->rajasms->set_number('08xxxxxxxxxx');</code></li>
      <li><code>$this->rajasms->set_text('Isi SMS');</code></li>
      <li><code>$sms_result = $this->rajasms->send();</code></li>
    <ul>
  </li>
  <li>
    Mengambil status laporan SMS:
    <ul>
      <li><code>echo $this->rajasms->get_report($sms_result);</code></li>
    <ul>
  </li>
  <li>
    Menghapus nomor ponsel tujuan dan isi SMS:
    <ul>
      <li><code>echo $this->rajasms->reset();</code></li>
    <ul>
  </li>
  <li>
    Cek saldo:
    <ul>
      <li><code>echo $this->rajasms->get_credit();</code></li>
    <ul>
  </li>
  <li>
    Cek waktu kedaluarsa <i>(unix timestamp)</i>:
    <ul>
      <li><code>echo $this->rajasms->get_expire_timestamp();</code></li>
    <ul>
  </li>
  <li>
    Cek tanggal kedaluarsa:
    <ul>
      <li><code>echo $this->rajasms->get_expire_date('Y-m-d H:i:s');</code></li>
    <ul>
  </li>
</ol>
