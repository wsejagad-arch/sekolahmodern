<?php
$file = 'c:\xampp\htdocs\jurnal\pages\siswa\jurnal-7kih.php';
$content = file_get_contents($file);

// 1. Add script tag before </head>
$content = str_replace('</head>', "    <script defer src=\"../../assets/js/face-api.min.js\"></script>\n</head>", $content);

// 2. Add Face Detection Overlay to camera modal
$old_video_block = '<div class="relative rounded-2xl overflow-hidden bg-slate-900 aspect-[3/4] mb-4">
            <video id="video" class="w-full h-full object-cover" autoplay playsinline></video>
            <img id="previewImg" class="w-full h-full object-cover hidden" alt="Preview">
        </div>';

$new_video_block = '<div class="relative rounded-2xl overflow-hidden bg-slate-900 aspect-[3/4] mb-4">
            <video id="video" class="w-full h-full object-cover" autoplay playsinline></video>
            <img id="previewImg" class="w-full h-full object-cover hidden" alt="Preview">
            <!-- Face Detection Overlay -->
            <div id="faceOverlay" class="absolute inset-0 flex items-center justify-center pointer-events-none z-10">
                <div id="faceBox" class="w-[65%] aspect-[3/4] rounded-[50%] border-4 border-dashed border-white/50 transition-all duration-300 flex flex-col items-center justify-center shadow-[0_0_0_9999px_rgba(0,0,0,0.5)]">
                   <div id="faceIcon" class="text-white/60 text-4xl mb-2 transition-colors duration-300"><i class="fa-solid fa-user-astronaut"></i></div>
                   <div id="faceText" class="text-white text-[10px] font-bold text-center px-3 py-1 bg-black/40 rounded-full transition-colors duration-300 backdrop-blur-sm">Posisikan wajah di area ini</div>
                </div>
            </div>
        </div>';
$content = str_replace($old_video_block, $new_video_block, $content);

// 3. Add face-api logic to JS
$js_vars = "const faceBox = document.getElementById('faceBox');
const faceIcon = document.getElementById('faceIcon');
const faceText = document.getElementById('faceText');
const faceOverlay = document.getElementById('faceOverlay');
let isFaceApiLoaded = false;
let faceDetectionInterval = null;

Promise.all([
    faceapi.nets.tinyFaceDetector.loadFromUri('../../assets/models'),
    faceapi.nets.faceLandmark68TinyNet.loadFromUri('../../assets/models')
]).then(() => {
    isFaceApiLoaded = true;
    console.log('Face API Models Loaded');
}).catch(err => console.error('Gagal memuat model:', err));

async function startFaceDetection() {
    if (!isFaceApiLoaded) return;
    
    const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.5 });
    
    faceDetectionInterval = setInterval(async () => {
        if (video.paused || video.ended || previewImg.classList.contains('hidden') === false) return;
        
        const detection = await faceapi.detectSingleFace(video, options).withFaceLandmarks(true);
        
        if (detection) {
            faceBox.classList.replace('border-white/50', 'border-emerald-400');
            faceBox.classList.replace('border-dashed', 'border-solid');
            faceIcon.classList.replace('text-white/60', 'text-emerald-400');
            faceText.textContent = 'Wajah terdeteksi, silakan ambil foto';
            faceText.classList.replace('bg-black/40', 'bg-emerald-600/90');
            
            document.getElementById('btnCapture').disabled = false;
            document.getElementById('btnCapture').classList.remove('opacity-50', 'pointer-events-none');
        } else {
            faceBox.classList.replace('border-emerald-400', 'border-white/50');
            faceBox.classList.replace('border-solid', 'border-dashed');
            faceIcon.classList.replace('text-emerald-400', 'text-white/60');
            faceText.textContent = 'Posisikan wajah di area ini';
            faceText.classList.replace('bg-emerald-600/90', 'bg-black/40');
            
            document.getElementById('btnCapture').disabled = true;
            document.getElementById('btnCapture').classList.add('opacity-50', 'pointer-events-none');
        }
    }, 200);
}

function stopFaceDetection() {
    if (faceDetectionInterval) {
        clearInterval(faceDetectionInterval);
        faceDetectionInterval = null;
    }
}
";

// Insert JS vars right after the existing DOM element selections
$old_dom = "const keteranganWrapper = document.getElementById('keteranganWrapper');\nconst keteranganInput = document.getElementById('keterangan');";
$content = str_replace($old_dom, $old_dom . "\n\n" . $js_vars, $content);

// Modify openCamera to disable btnCapture and startFaceDetection
$open_cam_success = "stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
        video.srcObject = stream;
        modalMsg.textContent = 'Posisikan wajah dan aktivitas terlihat jelas.';";

$open_cam_success_new = "stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
        video.srcObject = stream;
        
        document.getElementById('btnCapture').disabled = true;
        document.getElementById('btnCapture').classList.add('opacity-50', 'pointer-events-none');
        faceOverlay.classList.remove('hidden');
        
        if (!isFaceApiLoaded) {
            modalMsg.textContent = 'Memuat modul pendeteksi wajah... tunggu sebentar.';
            const checkLoad = setInterval(() => {
                if (isFaceApiLoaded) {
                    clearInterval(checkLoad);
                    modalMsg.textContent = 'Posisikan mata, hidung, dan mulut terlihat jelas.';
                    startFaceDetection();
                }
            }, 500);
        } else {
            modalMsg.textContent = 'Posisikan mata, hidung, dan mulut terlihat jelas.';
            startFaceDetection();
        }";
$content = str_replace($open_cam_success, $open_cam_success_new, $content);

// Modify closeCamera to stop face detection
$close_cam = "function closeCamera() {
    if (stream) {";
$close_cam_new = "function closeCamera() {
    stopFaceDetection();
    if (stream) {";
$content = str_replace($close_cam, $close_cam_new, $content);

// Modify capturePhoto to hide overlay
$capture_photo = "previewImg.src = canvas.toDataURL('image/jpeg', 0.7);
    previewImg.classList.remove('hidden');
    video.classList.add('hidden');";
$capture_photo_new = "previewImg.src = canvas.toDataURL('image/jpeg', 0.7);
    previewImg.classList.remove('hidden');
    video.classList.add('hidden');
    faceOverlay.classList.add('hidden');
    stopFaceDetection();";
$content = str_replace($capture_photo, $capture_photo_new, $content);

file_put_contents($file, $content);
echo "Applied Face Detection Logic.";
?>
