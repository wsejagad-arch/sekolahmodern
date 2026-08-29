<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const isPasswordHidden = ref(true);
const togglePassword = () => {
    isPasswordHidden.value = !isPasswordHidden.value;
};
</script>

<template>
    <Head title="Login SIMANIS" />

    <div class="page-shell">
        <!-- Panel Kiri: Hero Banner -->
        <aside class="hero-panel">
            <div class="ambient-glow">
                <div class="blob blob-1"></div>
                <div class="blob blob-2"></div>
                <div class="blob blob-3"></div>
            </div>
            <div class="hero-content">
                <div class="brand-mark">
                    <img src="/sekolahku/img/logo dash.png" alt="Logo SIMANIS">
                </div>
                <h1 class="brand-title">SIMANIS</h1>
                <p class="brand-subtitle">
                    <span style="font-size: 0.95rem; opacity: 0.9; font-weight: 500; display: block; margin-bottom: 6px;">Sistem Informasi Manajemen Akademik</span>
                    <span style="font-weight: 800; font-size: 1.25rem; display: block; letter-spacing: 0.03em; text-transform: uppercase; color: #fff;">SMAN 1 SUMBER</span>
                </p>
            </div>
        </aside>

        <!-- Panel Kanan: Form Login -->
        <section class="form-panel">
            <div class="form-card">

                <div v-if="status" class="alert alert-success">
                    {{ status }}
                </div>
                
                <div v-if="form.errors.login || form.errors.password" class="alert alert-danger">
                    {{ form.errors.login || form.errors.password }}
                </div>

                <form @submit.prevent="submit">
                    <div class="form-group">
                        <label class="form-label" for="login">Username / NIP / NIS / Email</label>
                        <div class="input-wrap">
                            <i class="bi bi-person input-icon"></i>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="login" 
                                v-model="form.login" 
                                placeholder="Ketikkan username Anda" 
                                required 
                                autofocus 
                                autocomplete="username"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-wrap">
                            <i class="bi bi-lock input-icon"></i>
                            <input 
                                :type="isPasswordHidden ? 'password' : 'text'" 
                                class="form-control" 
                                id="password" 
                                v-model="form.password" 
                                placeholder="Ketikkan password Anda" 
                                required 
                                autocomplete="current-password"
                            >
                            <button type="button" class="password-toggle" @click="togglePassword" aria-label="Tampilkan password">
                                <i :class="isPasswordHidden ? 'bi bi-eye' : 'bi bi-eye-slash'"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn" :class="{ 'opacity-50 cursor-not-allowed': form.processing }" :disabled="form.processing">
                        Masuk Sekarang
                    </button>
                </form>

                <div style="text-align: center; margin-top: 20px;" v-if="canResetPassword">
                    <Link :href="route('password.request')" style="color: #64748b; text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: color 0.3s;" onmouseover="this.style.color='#4F46E5'" onmouseout="this.style.color='#64748b'">Lupa Password?</Link>
                </div>

                <div class="footer-note">© {{ new Date().getFullYear() }} SIMANIS - Hak Cipta Dilindungi</div>
            </div>
        </section>
    </div>
</template>

<style scoped>
:root {
    --primary: #4F46E5;
    --primary-glow: rgba(79, 70, 229, 0.4);
    --bg-a: #0F172A;
    --bg-b: #1E1B4B;
    --panel: #FFFFFF;
    --text: #0F172A;
    --muted: #64748B;
    --input: #F8FAFC;
}

* {
    box-sizing: border-box;
}

:global(body) {
    margin: 0;
    padding: 0;
    min-height: 100vh;
    font-family: 'Nunito', 'Plus Jakarta Sans', sans-serif;
    background-color: var(--panel, #FFFFFF);
    color: var(--text, #0F172A);
    overflow-x: hidden;
}

/* Ambient Glow Blobs Styling for Hero Panel */
.ambient-glow {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    pointer-events: none;
    z-index: 0;
}

.blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.5;
    mix-blend-mode: screen;
    animation: float-blob 20s infinite alternate ease-in-out;
}

.blob-1 {
    top: -10%;
    left: -10%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, #10B981 0%, transparent 70%);
}

.blob-2 {
    bottom: -10%;
    right: -10%;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, #059669 0%, transparent 70%);
    animation-delay: -5s;
    animation-duration: 25s;
}

.blob-3 {
    top: 40%;
    left: 30%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, #34D399 0%, transparent 70%);
    animation-delay: -10s;
    animation-duration: 15s;
}

@keyframes float-blob {
    0% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(5%, 10%) scale(1.1); }
    100% { transform: translate(-5%, -5%) scale(0.9); }
}

.page-shell {
    display: flex;
    min-height: 100vh;
    width: 100vw;
}

.hero-panel {
    flex: 1.2;
    background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    color: #fff;
    padding: 40px;
    overflow: hidden;
}

/* Glassmorphism card inside hero */
.hero-content {
    z-index: 2;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    padding: 50px 40px;
    border-radius: 30px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
    align-items: center;
    max-width: 480px;
    width: 100%;
}

.hero-panel::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image: linear-gradient(rgba(255, 255, 255, 0.07) 1px, transparent 1px),
                        linear-gradient(90deg, rgba(255, 255, 255, 0.07) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
    z-index: 1;
}

.brand-mark {
    width: 120px;
    height: 120px;
    border-radius: 28px;
    background: #ffffff;
    display: grid;
    place-items: center;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
    padding: 15px;
    margin-bottom: 25px;
    transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.brand-mark:hover {
    transform: translateY(-8px) scale(1.05);
}

.brand-mark img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.brand-title {
    margin: 0;
    font-size: clamp(2rem, 3.5vw, 2.8rem);
    font-weight: 900;
    letter-spacing: .05em;
    background: linear-gradient(to right, #fff, #cbd5e1);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.brand-subtitle {
    margin: 15px 0 0;
    color: rgba(255, 255, 255, 0.85);
    font-size: 1.05rem;
    line-height: 1.6;
}

.form-panel {
    flex: 1;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 40px;
    position: relative;
    z-index: 5;
    box-shadow: -20px 0 40px rgba(0,0,0,0.05);
}

.form-card {
    width: 100%;
    max-width: 440px;
}

.form-heading {
    margin-bottom: 35px;
}

.form-heading h2 {
    margin: 0;
    font-size: 2.2rem;
    font-weight: 900;
    color: #0f172a;
}

.form-heading p {
    margin: 10px 0 0;
    color: #64748b;
    line-height: 1.6;
    font-size: 1.05rem;
}

.alert {
    border: none;
    border-radius: 16px;
    padding: 16px 20px;
    font-weight: 600;
    margin-bottom: 25px;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
}

.form-group {
    margin-bottom: 22px;
}

.form-label {
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 10px;
    display: block;
    font-size: 0.95rem;
}

.input-wrap {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1.2rem;
    pointer-events: none;
    transition: color 0.3s ease;
}

.form-control {
    width: 100%;
    min-height: 60px;
    border: 2px solid #e2e8f0;
    border-radius: 18px;
    background: #f8fafc;
    padding: 12px 20px 12px 52px;
    font-size: 1.05rem;
    font-weight: 500;
    color: #0f172a;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #4F46E5;
    background: #fff;
    box-shadow: 0 0 0 5px rgba(79, 70, 229, 0.1);
    outline: 0;
}

.form-control:focus + .input-icon,
.input-wrap:focus-within .input-icon {
    color: #4F46E5;
}

.password-toggle {
    position: absolute;
    right: 18px;
    top: 50%;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    color: #64748b;
    font-size: 1.2rem;
    cursor: pointer;
    transition: color 0.3s ease;
}

.password-toggle:hover {
    color: #4F46E5;
}

.submit-btn {
    width: 100%;
    margin-top: 15px;
    min-height: 60px;
    border: 0;
    border-radius: 18px;
    font-weight: 800;
    font-size: 1.1rem;
    letter-spacing: .02em;
    color: #fff;
    background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
    box-shadow: 0 10px 25px rgba(79, 70, 229, 0.4);
    transition: all 0.3s ease;
    cursor: pointer;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 35px rgba(79, 70, 229, 0.5);
}

.submit-btn:active {
    transform: translateY(0);
}

.footer-note {
    margin-top: 35px;
    color: #94a3b8;
    font-size: 0.9rem;
    text-align: center;
    font-weight: 500;
}

@media (max-width: 992px) {
    .page-shell {
        flex-direction: column;
    }
    .hero-panel {
        flex: none;
        padding: 60px 20px;
        border-radius: 0 0 40px 40px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .hero-content {
        padding: 40px 30px;
        box-shadow: none;
        background: transparent;
        border: none;
        backdrop-filter: none;
    }
    .form-panel {
        padding: 50px 20px;
        box-shadow: none;
    }
    .brand-mark {
        width: 90px;
        height: 90px;
        padding: 12px;
        margin-bottom: 20px;
    }
    .brand-title {
        font-size: 2.2rem;
    }
}

@media (max-width: 576px) {
    .hero-panel {
        padding: 40px 15px;
        border-radius: 0 0 30px 30px;
    }
    .hero-content {
        padding: 20px 10px;
    }
    .form-panel {
        padding: 40px 15px;
    }
    .form-heading h2 {
        font-size: 1.8rem;
    }
    .form-control, .submit-btn {
        min-height: 55px;
    }
}
</style>
