// Quotes System - Welcome Popup Only
const quotesDatabase = {
  dedication: [
    "Dedikasi adalah kunci untuk membuka pintu kesuksesan.",
    "Dengan dedikasi penuh, tidak ada yang tidak mungkin.",
    "Dedikasi membangun karakter yang kuat.",
    "Pembelajaran adalah buah dari dedikasi yang konsisten.",
    "Dedikasi guru mengubah masa depan siswa.",
    "Kesuksesan lahir dari dedikasi yang tulus.",
    "Dedikasi adalah investasi terbaik untuk masa depan.",
    "Dengan dedikasi, setiap tantangan menjadi peluang.",
    "Dedikasi membedakan antara guru biasa dan guru luar biasa.",
    "Kualitas pendidikan tergantung pada dedikasi guru.",
    "Dedikasi adalah fondasi dari setiap pencapaian besar.",
    "Tidak ada jalan pintas untuk kesuksesan selain dedikasi.",
    "Dedikasi menciptakan dampak yang berkelanjutan.",
    "Setiap hari adalah kesempatan untuk menunjukkan dedikasi.",
    "Dedikasi yang tulus selalu membuahkan hasil."
  ],
  love: [
    "Cinta pada profesi membuat setiap hari berarti.",
    "Mengajar dengan cinta mengubah hidup siswa.",
    "Cinta adalah kekuatan terbesar dalam pendidikan.",
    "Dengan cinta, setiap pelajaran menjadi bermakna.",
    "Cinta pada ilmu pengetahuan menular kepada siswa.",
    "Guru yang mengajar dengan cinta tidak pernah dilupakan.",
    "Cinta membuat proses belajar menjadi menyenangkan.",
    "Pendidikan yang dilandasi cinta menghasilkan generasi terbaik.",
    "Cinta pada siswa menciptakan lingkungan belajar yang positif.",
    "Dengan cinta, setiap kesulitan dalam mengajar dapat diatasi.",
    "Cinta adalah bahasa universal dalam pendidikan.",
    "Mengajar dengan cinta adalah investasi untuk masa depan.",
    "Cinta pada pendidikan menciptakan inovasi pembelajaran.",
    "Setiap siswa membutuhkan cinta dan perhatian guru.",
    "Cinta membuat profesi guru menjadi panggilan jiwa."
  ],
  friendship: [
    "Persahabatan dengan siswa membangun kepercayaan.",
    "Guru terbaik adalah yang menjadi sahabat siswa.",
    "Persahabatan menciptakan suasana belajar yang kondusif.",
    "Dengan persahabatan, komunikasi menjadi lebih efektif.",
    "Persahabatan guru-siswa adalah fondasi pembelajaran.",
    "Sahabat adalah guru terbaik dalam hidup.",
    "Persahabatan mengajarkan nilai-nilai kehidupan.",
    "Dengan persahabatan, setiap masalah dapat diselesaikan bersama.",
    "Persahabatan membuat ruang kelas menjadi rumah kedua.",
    "Guru yang bersahabat dengan siswa memiliki pengaruh yang besar.",
    "Persahabatan mengajarkan empati dan pengertian.",
    "Dengan persahabatan, belajar menjadi lebih menyenangkan.",
    "Persahabatan adalah jembatan menuju pemahaman yang lebih baik.",
    "Sahabat sejati adalah yang saling menginspirasi.",
    "Persahabatan dalam pendidikan menciptakan kenangan indah."
  ],
  motivation: [
    "Setiap hari adalah kesempatan baru untuk berkembang.",
    "Guru yang termotivasi menciptakan siswa yang termotivasi.",
    "Motivasi adalah bahan bakar untuk mencapai impian.",
    "Dengan motivasi tinggi, setiap tantangan dapat diatasi.",
    "Motivasi mengubah mimpi menjadi kenyataan.",
    "Guru adalah sumber motivasi bagi siswa-siswanya.",
    "Motivasi membuat yang tidak mungkin menjadi mungkin.",
    "Setiap siswa membutuhkan motivasi untuk bangkit.",
    "Dengan motivasi, belajar menjadi petualangan yang menarik.",
    "Motivasi adalah kunci untuk membuka potensi diri.",
    "Guru yang memotivasi mengubah hidup siswa.",
    "Motivasi menciptakan semangat yang tak pernah padam.",
    "Dengan motivasi, setiap kegagalan menjadi pelajaran.",
    "Motivasi adalah langkah pertama menuju kesuksesan.",
    "Pendidikan yang memotivasi menghasilkan generasi unggul."
  ],
  wisdom: [
    "Kebijaksanaan adalah hasil dari pengalaman dan refleksi.",
    "Guru bijak mengajarkan lebih dari sekedar materi pelajaran.",
    "Kebijaksanaan mengajarkan kita kapan harus berbicara dan kapan harus mendengar.",
    "Dengan kebijaksanaan, setiap keputusan diambil dengan pertimbangan matang.",
    "Kebijaksanaan adalah kemampuan melihat hikmah di balik setiap peristiwa.",
    "Guru yang bijak menjadi panutan bagi siswa-siswanya.",
    "Kebijaksanaan mengajarkan kita untuk memahami, bukan sekedar menghafal.",
    "Dengan kebijaksanaan, konflik dapat diselesaikan dengan damai.",
    "Kebijaksanaan adalah cahaya yang menerangi jalan pendidikan.",
    "Guru bijak membantu siswa menemukan jati diri mereka.",
    "Kebijaksanaan mengajarkan nilai-nilai kehidupan yang sesungguhnya.",
    "Dengan kebijaksanaan, setiap kata memiliki makna yang mendalam.",
    "Kebijaksanaan adalah warisan terbaik yang dapat diberikan guru.",
    "Pendidikan yang bijak menghasilkan generasi yang berkarakter.",
    "Kebijaksanaan membedakan antara pengetahuan dan pemahaman."
  ]
};

// Penentuan tema waktu & greeting
function getTimeMeta() {
  const hour = new Date().getHours();
  if (hour >= 5 && hour < 11) return { theme: 'morning', greeting: 'Selamat pagi', category: 'motivation', wish: 'Semoga sesi mengajarnya penuh semangat!' };
  if (hour >= 11 && hour < 15) return { theme: 'afternoon', greeting: 'Selamat siang', category: 'wisdom', wish: 'Tetap fokus dan menginspirasi siswa.' };
  if (hour >= 15 && hour < 18) return { theme: 'evening', greeting: 'Selamat sore', category: 'dedication', wish: 'Terima kasih atas dedikasinya hari ini.' };
  return { theme: 'night', greeting: 'Selamat malam', category: 'friendship', wish: 'Istirahat yang cukup untuk hari esok.' };
}

// Ambil quote berdasarkan kategori meta waktu
function getTimeBasedQuote() {
  const meta = getTimeMeta();
  let category = meta.category;
  if (meta.theme === 'night' && new Date().getHours() >= 19 && new Date().getHours() < 22) {
    category = 'love'; // transisi malam awal pakai love
  }
  const quotes = quotesDatabase[category];
  const randomIndex = Math.floor(Math.random() * quotes.length);
  return { text: quotes[randomIndex], category };
}

// Fungsi untuk menampilkan welcome popup
function showWelcomePopup(firstName, options = {}) {
  const defaultOptions = { autoClose: false, showNewQuoteButton: true, sessionKey: 'welcomePopupShown' };
  const config = { ...defaultOptions, ...options };
  const meta = getTimeMeta();
  const quote = getTimeBasedQuote();

  const popupHTML = `
  <div id="welcomePopup" class="welcome-popup-overlay">
    <div class="welcome-popup ${meta.theme}">
      <button class="welcome-popup-close" type="button" aria-label="Tutup" onclick="closeWelcomePopup()">&times;</button>
      <div class="welcome-greeting">
        <h3>${meta.greeting}, <span class="user-name">${firstName}</span>! 👋</h3>
        <p class="greeting-subtitle">${meta.wish}</p>
      </div>
      <div class="popup-quote-content">
        <div class="popup-quote-icon">💡</div>
        <div class="popup-quote-text" id="currentQuote">${quote.text}</div>
        <div class="popup-quote-category" id="quoteCategory">${quote.category.toUpperCase()}</div>
      </div>
      <!-- Tombol dihapus sesuai permintaan -->
    </div>
  </div>`;

  // Masukkan ke body
  document.body.insertAdjacentHTML('beforeend', popupHTML);

  // Event tombol
  const btnNew = document.getElementById('btnNewQuote');
  if (btnNew) btnNew.addEventListener('click', getNewQuote);
  const btnClose = document.getElementById('btnClosePopup');
  if (btnClose) btnClose.addEventListener('click', closeWelcomePopup);

  if (config.autoClose && typeof config.autoClose === 'number') {
    setTimeout(closeWelcomePopup, config.autoClose);
  }
  if (config.sessionKey) sessionStorage.setItem(config.sessionKey, 'true');
}

// Fungsi untuk menutup welcome popup
function closeWelcomePopup() {
  const overlay = document.getElementById('welcomePopup');
  if (!overlay) return;
  overlay.classList.add('closing');
  setTimeout(()=> overlay.remove(), 320);
}

// Fungsi untuk mendapatkan quote baru
function getNewQuote() {
  const quote = getTimeBasedQuote();
  const elText = document.getElementById('currentQuote');
  const elCat = document.getElementById('quoteCategory');
  if (!elText || !elCat) return;
  elText.classList.add('switching');
  elCat.classList.add('switching');
  setTimeout(()=>{
    elText.textContent = quote.text;
    elCat.textContent = quote.category.toUpperCase();
    elText.classList.remove('switching');
    elCat.classList.remove('switching');
  },180);
}

// Fungsi untuk inisialisasi welcome popup dengan kontrol session
function initWelcomePopup(firstName, options = {}) {
  const defaultOptions = { sessionKey: 'welcomePopupShown', forceShow: false };
  const config = { ...defaultOptions, ...options };
  const shown = sessionStorage.getItem(config.sessionKey);
  if (!shown || config.forceShow) setTimeout(()=> showWelcomePopup(firstName, config), 450);
}

// Event listeners untuk keyboard shortcuts
document.addEventListener('keydown', function(e) {
  // Escape key untuk menutup popup
  if (e.key === 'Escape') {
    closeWelcomePopup();
  }
  
  // Space key untuk quote baru (hanya jika popup terbuka)
  if (e.key === ' ' && document.getElementById('welcomePopup')) {
    e.preventDefault();
    getNewQuote();
  }
});

// Click outside popup untuk menutup
document.addEventListener('click', function(e){
  const overlay = document.getElementById('welcomePopup');
  if (overlay && e.target === overlay) closeWelcomePopup();
});

// Optional: expose for debugging
window.WelcomePopupQuoteRefresh = getNewQuote;