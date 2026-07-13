import re

css_to_add = """
        /* JOURNAL MODAL CSS */
        .modal-open-dashboard { overflow: hidden; }
        .journal-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px 14px;
            background: rgba(15, 23, 42, 0.52);
            backdrop-filter: blur(12px);
            overflow-y: auto;
        }
        .journal-modal-backdrop.is-open { display: flex; }
        .journal-modal {
            width: min(100%, 720px);
            max-height: min(82vh, 760px);
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            margin: auto;
        }
        .journal-modal-sm { width: min(100%, 420px); }
        .journal-modal-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            padding: 18px 20px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            background: linear-gradient(180deg, #ffffff, #f8fafc);
        }
        .journal-modal-head h5 {
            margin: 0;
            color: #0f172a;
            font-size: 16px;
            font-weight: 600;
        }
        .journal-modal-head p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 11px;
        }
        .journal-modal-close {
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 12px;
            background: #f1f5f9;
            color: #475569;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
        }
        .journal-modal-body {
            padding: 18px;
            overflow-y: auto;
            min-height: 0;
            flex: 1 1 auto;
        }
        @media (max-width: 540px) {
            .journal-modal-backdrop {
                align-items: flex-end;
                justify-content: center;
                padding: 0;
            }
            .journal-modal {
                width: 100vw;
                max-height: 92vh;
                border-radius: 28px 28px 0 0;
                margin: 0;
                padding-bottom: max(16px, env(safe-area-inset-bottom));
                animation: slideUpModal 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }
            .journal-modal-head { padding: 20px 24px 16px; }
            .journal-modal-body { padding: 0 24px 24px; }
        }
        @keyframes slideUpModal {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
"""

with open('pages/guru/materi.php', 'r', encoding='utf-8') as f:
    content = f.read()

if ".journal-modal-backdrop {" not in content:
    content = content.replace("<style>", "<style>" + css_to_add)
    with open('pages/guru/materi.php', 'w', encoding='utf-8') as f:
        f.write(content)
    print("CSS modal added.")
else:
    print("CSS modal already present.")
