<?php
/**
 * Bahasa tampilan untuk pesan dari server.
 *
 * Bahasa dipilih di browser (assets/app.js) dan disimpan di cookie kpi_lang, jadi pesan galat
 * dari api.php ikut memakai bahasa yang sama dengan tampilan. Teks bahasa Indonesia sekaligus
 * menjadi kunci; terjemahan tampilan ada di assets/i18n.js.
 */

/** Bahasa yang tersedia: kode => [atribut lang HTML, arah tulisan]. */
const KPI_LANGS = [
    'id' => ['id', 'ltr'],
    'zh' => ['zh-CN', 'ltr'],
    'ja' => ['ja', 'ltr'],
    'ar' => ['ar', 'rtl'],
    'en' => ['en', 'ltr'],
    'fr' => ['fr', 'ltr'],
    'de' => ['de', 'ltr'],
    'es' => ['es', 'ltr'],
];
const KPI_LANG_DEFAULT = 'id';
const KPI_LANG_COOKIE  = 'kpi_lang';
/** Urutan kolom terjemahan di kpi_messages(). */
const KPI_LANG_ORDER = ['en', 'zh', 'ja', 'ar', 'fr', 'de', 'es'];

/** Nama asli tiap bahasa, untuk pilihan bahasa di halaman admin. */
const KPI_LANG_ENDONYMS = [
    'id' => 'Bahasa Indonesia', 'zh' => '简体中文', 'ja' => '日本語', 'ar' => 'العربية',
    'en' => 'English', 'fr' => 'Français', 'de' => 'Deutsch', 'es' => 'Español',
];

/** Nama tiap bahasa, ditulis dalam bahasa yang sedang dipakai (sama dengan "names" di assets/i18n.js). */
const KPI_LANG_NAMES = [
    'id' => ['id' => 'Indonesia', 'zh' => 'Mandarin', 'ja' => 'Jepang', 'ar' => 'Arab', 'en' => 'Inggris', 'fr' => 'Prancis', 'de' => 'Jerman', 'es' => 'Spanyol'],
    'zh' => ['id' => '印尼语', 'zh' => '中文', 'ja' => '日语', 'ar' => '阿拉伯语', 'en' => '英语', 'fr' => '法语', 'de' => '德语', 'es' => '西班牙语'],
    'ja' => ['id' => 'インドネシア語', 'zh' => '中国語', 'ja' => '日本語', 'ar' => 'アラビア語', 'en' => '英語', 'fr' => 'フランス語', 'de' => 'ドイツ語', 'es' => 'スペイン語'],
    'ar' => ['id' => 'الإندونيسية', 'zh' => 'الصينية', 'ja' => 'اليابانية', 'ar' => 'العربية', 'en' => 'الإنجليزية', 'fr' => 'الفرنسية', 'de' => 'الألمانية', 'es' => 'الإسبانية'],
    'en' => ['id' => 'Indonesian', 'zh' => 'Chinese', 'ja' => 'Japanese', 'ar' => 'Arabic', 'en' => 'English', 'fr' => 'French', 'de' => 'German', 'es' => 'Spanish'],
    'fr' => ['id' => 'Indonésien', 'zh' => 'Chinois', 'ja' => 'Japonais', 'ar' => 'Arabe', 'en' => 'Anglais', 'fr' => 'Français', 'de' => 'Allemand', 'es' => 'Espagnol'],
    'de' => ['id' => 'Indonesisch', 'zh' => 'Chinesisch', 'ja' => 'Japanisch', 'ar' => 'Arabisch', 'en' => 'Englisch', 'fr' => 'Französisch', 'de' => 'Deutsch', 'es' => 'Spanisch'],
    'es' => ['id' => 'Indonesio', 'zh' => 'Chino', 'ja' => 'Japonés', 'ar' => 'Árabe', 'en' => 'Inglés', 'fr' => 'Francés', 'de' => 'Alemán', 'es' => 'Español'],
];

/** Pengelompokan di pilihan bahasa: judul kelompok => kode bahasa. */
const KPI_LANG_REGIONS = [
    'Asia'  => ['id', 'zh', 'ja', 'ar'],
    'Eropa' => ['en', 'fr', 'de', 'es'],
];

/** Bahasa pilihan pengguna yang tersimpan di cookie, atau null bila belum memilih. */
function kpi_lang_cookie(): ?string
{
    $c = $_COOKIE[KPI_LANG_COOKIE] ?? null;
    return is_string($c) && isset(KPI_LANGS[$c]) ? $c : null;
}

function kpi_lang(): string
{
    return kpi_lang_cookie() ?? KPI_LANG_DEFAULT;
}

function kpi_lang_html(): string
{
    return KPI_LANGS[kpi_lang()][0];
}

function kpi_lang_dir(): string
{
    return KPI_LANGS[kpi_lang()][1];
}

/**
 * Terjemahkan teks ke bahasa yang sedang dipakai. {0}, {1}, ... diganti dengan $args.
 * Teks yang belum punya terjemahan dikembalikan apa adanya.
 */
function kpi_t(string $text, ...$args): string
{
    $lang = kpi_lang();
    if ($lang !== KPI_LANG_DEFAULT) {
        $i   = array_search($lang, KPI_LANG_ORDER, true);
        $row = kpi_messages()[$text] ?? null;
        if ($i !== false && $row !== null && ($row[$i] ?? '') !== '') {
            $text = $row[$i];
        }
    }
    if ($args) {
        $text = preg_replace_callback('/\{(\d+)\}/', static function ($m) use ($args) {
            return (string)($args[(int)$m[1]] ?? '');
        }, $text);
    }
    return $text;
}

/** Angka dengan pemisah ribuan dan desimal yang lazim di bahasa yang sedang dipakai. */
function kpi_num(float $n, int $dec = 0): string
{
    switch (kpi_lang()) {
        case 'en': case 'zh': case 'ja': case 'ar':
            return number_format($n, $dec, '.', ',');
        case 'fr':
            return number_format($n, $dec, ',', "\u{202F}");
        default:
            return number_format($n, $dec, ',', '.');
    }
}

/** Format tanggal dan jam untuk tabel, mengikuti kebiasaan tiap bahasa. */
function kpi_date_format(): string
{
    switch (kpi_lang()) {
        case 'zh': case 'ja':
            return 'Y/m/d H:i';
        case 'de':
            return 'd.m.Y H:i';
        default:
            return 'd/m/Y H:i';
    }
}

/**
 * Terjemahan pesan server. Urutan kolom mengikuti KPI_LANG_ORDER.
 * Halaman admin (admin.php) menetapkan KPI_I18N_ADMIN supaya terjemahannya ikut dimuat.
 */
function kpi_messages(): array
{
    static $m = null;
    if ($m !== null) {
        return $m;
    }
    $m = [
        // Isian formulir
        'Isian "{0}" tidak valid.' => ["The field “{0}” is invalid.", "字段“{0}”无效。", "項目「{0}」が無効です。", "الحقل «{0}» غير صالح.", "Le champ « {0} » n'est pas valide.", "Das Feld „{0}“ ist ungültig.", "El campo «{0}» no es válido."],
        'Isian "{0}" harus berupa angka.' => ["The field “{0}” must be a number.", "字段“{0}”必须是数字。", "項目「{0}」は数値で入力してください。", "يجب أن يكون الحقل «{0}» رقمًا.", "Le champ « {0} » doit être un nombre.", "Das Feld „{0}“ muss eine Zahl sein.", "El campo «{0}» debe ser un número."],
        'Isian "{0}" wajib diisi.' => ["The field “{0}” is required.", "字段“{0}”为必填项。", "項目「{0}」は必須です。", "الحقل «{0}» مطلوب.", "Le champ « {0} » est obligatoire.", "Das Feld „{0}“ ist erforderlich.", "El campo «{0}» es obligatorio."],

        // Anggota
        'Nama wajib diisi.' => ["Name is required.", "姓名为必填项。", "名前は必須です。", "الاسم مطلوب.", "Le nom est obligatoire.", "Der Name ist erforderlich.", "El nombre es obligatorio."],
        'Username wajib diisi dengan huruf atau angka.' => ["The username must contain letters or numbers.", "用户名必须包含字母或数字。", "ユーザー名には英字または数字を入力してください。", "يجب أن يحتوي اسم المستخدم على أحرف أو أرقام.", "Le nom d'utilisateur doit contenir des lettres ou des chiffres.", "Der Benutzername muss Buchstaben oder Ziffern enthalten.", "El usuario debe contener letras o números."],
        'Username "{0}" sudah dipakai. Gunakan username lain.' => ["The username “{0}” is already taken. Use another username.", "用户名“{0}”已被使用，请换一个。", "ユーザー名「{0}」はすでに使われています。別のユーザー名にしてください。", "اسم المستخدم «{0}» مستخدم بالفعل. اختر اسمًا آخر.", "Le nom d'utilisateur « {0} » est déjà pris. Choisissez-en un autre.", "Der Benutzername „{0}“ ist bereits vergeben. Bitte wählen Sie einen anderen.", "El usuario «{0}» ya está en uso. Elija otro."],
        'Anggota tidak ditemukan.' => ["Member not found.", "未找到该成员。", "メンバーが見つかりません。", "العضو غير موجود.", "Membre introuvable.", "Mitglied nicht gefunden.", "No se encontró el miembro."],
        'Atasan tidak ditemukan.' => ["Superior not found.", "未找到该上级。", "上司が見つかりません。", "الرئيس المباشر غير موجود.", "Supérieur introuvable.", "Führungskraft nicht gefunden.", "No se encontró el superior."],
        'Atasan baru harus berada di level yang sama dengan atasan sebelumnya.' => ["The new superior must be at the same level as the previous one.", "新上级必须与原上级处于同一级别。", "新しい上司は前の上司と同じレベルである必要があります。", "يجب أن يكون الرئيس الجديد في مستوى الرئيس السابق نفسه.", "Le nouveau supérieur doit être au même niveau que le précédent.", "Die neue Führungskraft muss auf derselben Ebene sein wie die bisherige.", "El nuevo superior debe estar en el mismo nivel que el anterior."],
        'Atasan tidak boleh berasal dari bawahannya sendiri.' => ["The superior cannot be one of the person's own subordinates.", "上级不能是其本人的下级。", "本人の部下を上司にすることはできません。", "لا يمكن أن يكون الرئيس المباشر من مرؤوسي الشخص نفسه.", "Le supérieur ne peut pas être l'un de ses propres subordonnés.", "Die Führungskraft darf keine eigene unterstellte Person sein.", "El superior no puede ser uno de sus propios subordinados."],
        'Password minimal 4 karakter.' => ["The password must be at least 4 characters.", "密码至少需要4个字符。", "パスワードは4文字以上にしてください。", "يجب ألا تقل كلمة المرور عن 4 أحرف.", "Le mot de passe doit comporter au moins 4 caractères.", "Das Passwort muss mindestens 4 Zeichen haben.", "La contraseña debe tener al menos 4 caracteres."],
        'Pilih atasan terlebih dahulu.' => ["Choose a superior first.", "请先选择上级。", "先に上司を選択してください。", "اختر الرئيس المباشر أولًا.", "Choisissez d'abord un supérieur.", "Wählen Sie zuerst eine Führungskraft.", "Elija primero un superior."],
        'Staff tidak dapat memiliki bawahan.' => ["Staff cannot have subordinates.", "员工不能有下级。", "スタッフは部下を持てません。", "لا يمكن أن يكون للموظف مرؤوسون.", "Un employé ne peut pas avoir de subordonnés.", "Mitarbeiter können keine unterstellten Personen haben.", "Un empleado no puede tener subordinados."],
        'Pindahkan dulu bawahannya ke atasan lain sebelum menghapus.' => ["Move this person's subordinates to another superior before deleting.", "删除前请先将其下级转给其他上级。", "削除する前に、部下を別の上司に移してください。", "انقل مرؤوسيه إلى رئيس آخر قبل الحذف.", "Transférez d'abord ses subordonnés à un autre supérieur avant de supprimer.", "Verschieben Sie zuerst die Mitarbeiter zu einer anderen Führungskraft.", "Mueva primero a sus subordinados a otro superior antes de eliminar."],
        'Anda tidak dapat menghapus akun Anda sendiri.' => ["You cannot delete your own account.", "您不能删除自己的账户。", "自分のアカウントは削除できません。", "لا يمكنك حذف حسابك.", "Vous ne pouvez pas supprimer votre propre compte.", "Sie können Ihr eigenes Konto nicht löschen.", "No puede eliminar su propia cuenta."],

        // Realisasi dan persetujuan
        'Periode tahun tidak sesuai dengan pengaturan.' => ["The year does not match the settings.", "年度与设置不一致。", "年度が設定と一致しません。", "السنة لا تطابق الإعدادات.", "L'année ne correspond pas aux paramètres.", "Das Jahr stimmt nicht mit den Einstellungen überein.", "El año no coincide con la configuración."],
        'Bulan tidak valid.' => ["Invalid month.", "月份无效。", "月が無効です。", "الشهر غير صالح.", "Mois non valide.", "Ungültiger Monat.", "Mes no válido."],
        'Mode penyimpanan tidak valid.' => ["Invalid save mode.", "保存方式无效。", "保存方法が無効です。", "طريقة الحفظ غير صالحة.", "Mode d'enregistrement non valide.", "Ungültiger Speichermodus.", "Modo de guardado no válido."],
        'Daftar realisasi tidak valid.' => ["Invalid list of actuals.", "实际值列表无效。", "実績の一覧が無効です。", "قائمة النتائج غير صالحة.", "Liste de résultats non valide.", "Ungültige Liste der Ist-Werte.", "Lista de resultados no válida."],
        'Anda hanya dapat mengisi realisasi KPI milik sendiri.' => ["You can only enter actuals for your own KPIs.", "您只能填写自己KPI的实际值。", "実績を入力できるのは自分のKPIだけです。", "يمكنك إدخال نتائج مؤشراتك فقط.", "Vous ne pouvez saisir que les résultats de vos propres KPI.", "Sie können nur Ist-Werte für Ihre eigenen KPIs erfassen.", "Solo puede registrar resultados de sus propios KPI."],
        'Realisasi tidak boleh bernilai negatif.' => ["Actuals cannot be negative.", "实际值不能为负数。", "実績にマイナスの値は入力できません。", "لا يمكن أن تكون النتيجة سالبة.", "Un résultat ne peut pas être négatif.", "Ist-Werte dürfen nicht negativ sein.", "Los resultados no pueden ser negativos."],
        'Anda hanya dapat menyetujui realisasi bawahan langsung Anda.' => ["You can only approve actuals of your direct reports.", "您只能审批直属下级的实际值。", "承認できるのは直属の部下の実績だけです。", "يمكنك الموافقة على نتائج مرؤوسيك المباشرين فقط.", "Vous ne pouvez approuver que les résultats de vos collaborateurs directs.", "Sie können nur Ist-Werte Ihrer direkten Mitarbeiter freigeben.", "Solo puede aprobar resultados de sus subordinados directos."],
        'Realisasi ini tidak sedang menunggu persetujuan.' => ["This actual is not awaiting approval.", "该实际值不在待审批状态。", "この実績は承認待ちではありません。", "هذه النتيجة ليست بانتظار الموافقة.", "Ce résultat n'est pas en attente d'approbation.", "Dieser Ist-Wert wartet nicht auf Freigabe.", "Este resultado no está pendiente de aprobación."],
        'Mohon diperiksa kembali.' => ["Please check it again.", "请重新检查。", "もう一度確認してください。", "يرجى المراجعة مرة أخرى.", "Merci de vérifier à nouveau.", "Bitte noch einmal prüfen.", "Revíselo de nuevo, por favor."],

        // KPI
        'KPI tidak ditemukan.' => ["KPI not found.", "未找到该KPI。", "KPIが見つかりません。", "المؤشر غير موجود.", "KPI introuvable.", "KPI nicht gefunden.", "No se encontró el KPI."],
        'Anda tidak berhak mengubah KPI pengguna ini.' => ["You are not allowed to change this user's KPIs.", "您无权修改该用户的KPI。", "このユーザーのKPIを変更する権限がありません。", "لا يحق لك تعديل مؤشرات هذا المستخدم.", "Vous n'êtes pas autorisé à modifier les KPI de cet utilisateur.", "Sie dürfen die KPIs dieser Person nicht ändern.", "No tiene permiso para cambiar los KPI de este usuario."],
        'Pemilik KPI tidak ditemukan.' => ["KPI owner not found.", "未找到KPI负责人。", "KPIの担当者が見つかりません。", "مسؤول المؤشر غير موجود.", "Responsable du KPI introuvable.", "Verantwortliche Person des KPI nicht gefunden.", "No se encontró el responsable del KPI."],
        'Nama KPI wajib diisi.' => ["KPI name is required.", "KPI名称为必填项。", "KPI名は必須です。", "اسم المؤشر مطلوب.", "Le nom du KPI est obligatoire.", "Der KPI-Name ist erforderlich.", "El nombre del KPI es obligatorio."],
        'Target harus angka lebih dari 0.' => ["The target must be a number greater than 0.", "目标必须是大于0的数字。", "目標は0より大きい数値にしてください。", "يجب أن يكون المستهدف رقمًا أكبر من 0.", "L'objectif doit être un nombre supérieur à 0.", "Das Ziel muss eine Zahl größer als 0 sein.", "El objetivo debe ser un número mayor que 0."],
        'Bobot harus antara 0 dan 100.' => ["The weight must be between 0 and 100.", "权重必须在0到100之间。", "ウェイトは0から100の間にしてください。", "يجب أن يكون الوزن بين 0 و100.", "Le poids doit être compris entre 0 et 100.", "Das Gewicht muss zwischen 0 und 100 liegen.", "El peso debe estar entre 0 y 100."],
        'Cara pengisian realisasi tidak valid.' => ["Invalid way of entering actuals.", "实际值填写方式无效。", "実績の入力方法が無効です。", "طريقة إدخال النتائج غير صالحة.", "Mode de saisie des résultats non valide.", "Ungültige Erfassungsart der Ist-Werte.", "Forma de registro de resultados no válida."],
        'Arah penilaian tidak valid.' => ["Invalid scoring direction.", "评分方向无效。", "評価の方向が無効です。", "اتجاه التقييم غير صالح.", "Sens d'évaluation non valide.", "Ungültige Bewertungsrichtung.", "Sentido de evaluación no válido."],
        'Perspektif tidak valid.' => ["Invalid perspective.", "维度无效。", "視点が無効です。", "المنظور غير صالح.", "Perspective non valide.", "Ungültige Perspektive.", "Perspectiva no válida."],
        'KPI induk harus milik atasan langsung pemilik KPI ini.' => ["The parent KPI must belong to the direct superior of this KPI's owner.", "上级KPI必须属于该KPI负责人的直属上级。", "上位KPIは、このKPIの担当者の直属の上司のものである必要があります。", "يجب أن يعود المؤشر الأصلي إلى الرئيس المباشر لمسؤول هذا المؤشر.", "Le KPI parent doit appartenir au supérieur direct du responsable de ce KPI.", "Der übergeordnete KPI muss der direkten Führungskraft der verantwortlichen Person gehören.", "El KPI superior debe pertenecer al superior directo del responsable de este KPI."],
        'KPI tidak boleh menjadi induk dirinya sendiri.' => ["A KPI cannot be its own parent.", "KPI不能以自身为上级。", "KPIを自分自身の上位KPIにすることはできません。", "لا يمكن أن يكون المؤشر أصلًا لنفسه.", "Un KPI ne peut pas être son propre parent.", "Ein KPI kann nicht sein eigener übergeordneter KPI sein.", "Un KPI no puede ser su propio KPI superior."],
        'Anda tidak berhak menghapus KPI ini.' => ["You are not allowed to delete this KPI.", "您无权删除该KPI。", "このKPIを削除する権限がありません。", "لا يحق لك حذف هذا المؤشر.", "Vous n'êtes pas autorisé à supprimer ce KPI.", "Sie dürfen diesen KPI nicht löschen.", "No tiene permiso para eliminar este KPI."],

        // Akun dan sesi
        'Password lama tidak cocok.' => ["The current password is incorrect.", "当前密码不正确。", "現在のパスワードが正しくありません。", "كلمة المرور الحالية غير صحيحة.", "Le mot de passe actuel est incorrect.", "Das aktuelle Passwort ist falsch.", "La contraseña actual no es correcta."],
        'Password baru minimal 4 karakter.' => ["The new password must be at least 4 characters.", "新密码至少需要4个字符。", "新しいパスワードは4文字以上にしてください。", "يجب ألا تقل كلمة المرور الجديدة عن 4 أحرف.", "Le nouveau mot de passe doit comporter au moins 4 caractères.", "Das neue Passwort muss mindestens 4 Zeichen haben.", "La nueva contraseña debe tener al menos 4 caracteres."],
        'Sesi Anda sudah berakhir. Silakan masuk kembali.' => ["Your session has expired. Please sign in again.", "您的会话已过期，请重新登录。", "セッションの有効期限が切れました。もう一度ログインしてください。", "انتهت جلستك. يرجى تسجيل الدخول مرة أخرى.", "Votre session a expiré. Veuillez vous reconnecter.", "Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an.", "Su sesión ha caducado. Inicie sesión de nuevo."],
        'Username atau password salah. Silakan coba lagi.' => ["Incorrect username or password. Please try again.", "用户名或密码错误，请重试。", "ユーザー名またはパスワードが正しくありません。もう一度お試しください。", "اسم المستخدم أو كلمة المرور غير صحيحة. حاول مرة أخرى.", "Nom d'utilisateur ou mot de passe incorrect. Veuillez réessayer.", "Benutzername oder Passwort ist falsch. Bitte versuchen Sie es erneut.", "Usuario o contraseña incorrectos. Inténtelo de nuevo."],
        'Terlalu banyak percobaan gagal. Coba lagi dalam {0} menit.' => ["Too many failed attempts. Try again in {0} minutes.", "失败次数过多，请在{0}分钟后重试。", "失敗が多すぎます。{0}分後にもう一度お試しください。", "محاولات فاشلة كثيرة. حاول مرة أخرى بعد {0} دقيقة.", "Trop de tentatives échouées. Réessayez dans {0} minutes.", "Zu viele Fehlversuche. Versuchen Sie es in {0} Minuten erneut.", "Demasiados intentos fallidos. Inténtelo de nuevo en {0} minutos."],
        'Ini akun admin, bukan akun anggota. Masuk lewat halaman admin: {0}' => ["This is an admin account, not a member account. Sign in on the admin page: {0}", "这是管理员账户，不是成员账户。请通过管理页面登录：{0}", "これは管理者アカウントで、メンバー用ではありません。管理ページからログインしてください：{0}", "هذا حساب مسؤول وليس حساب عضو. سجّل الدخول من صفحة الإدارة: {0}", "Ceci est un compte administrateur, pas un compte membre. Connectez-vous sur la page d'administration : {0}", "Dies ist ein Admin-Konto, kein Mitgliedskonto. Melden Sie sich auf der Admin-Seite an: {0}", "Esta es una cuenta de administrador, no de miembro. Inicie sesión en la página de administración: {0}"],
        'Hanya CEO atau Pengelola KPI yang dapat melakukan tindakan ini.' => ["Only the CEO or a KPI Manager can do this.", "只有CEO或KPI管理员可以执行此操作。", "この操作はCEOまたはKPI管理者のみ実行できます。", "لا يمكن تنفيذ هذا الإجراء إلا من قِبل الرئيس التنفيذي أو مدير مؤشرات الأداء.", "Seul le PDG ou un gestionnaire des KPI peut effectuer cette action.", "Nur der CEO oder ein KPI-Manager kann dies tun.", "Solo el CEO o un gestor de KPI puede hacer esto."],
        'Data CEO hanya bisa diubah oleh CEO sendiri.' => ["Only the CEO can change the CEO's details.", "CEO的资料只能由CEO本人修改。", "CEOの情報はCEO本人だけが変更できます。", "لا يمكن تعديل بيانات الرئيس التنفيذي إلا من قِبله.", "Seul le PDG peut modifier ses propres informations.", "Die Daten des CEO kann nur der CEO selbst ändern.", "Solo el CEO puede cambiar sus propios datos."],
        'Role tidak dikenal.' => ["Unknown role.", "未知角色。", "不明なロールです。", "دور غير معروف.", "Rôle inconnu.", "Unbekannte Rolle.", "Rol desconocido."],

        // Permintaan dan server
        'Aksi tidak dikenal.' => ["Unknown action.", "未知操作。", "不明な操作です。", "إجراء غير معروف.", "Action inconnue.", "Unbekannte Aktion.", "Acción desconocida."],
        'Aplikasi belum dipasang. Buka install.php terlebih dahulu.' => ["The app is not installed yet. Open install.php first.", "应用尚未安装，请先打开 install.php。", "アプリはまだインストールされていません。先に install.php を開いてください。", "لم يُثبَّت التطبيق بعد. افتح install.php أولًا.", "L'application n'est pas encore installée. Ouvrez d'abord install.php.", "Die App ist noch nicht installiert. Öffnen Sie zuerst install.php.", "La aplicación aún no está instalada. Abra primero install.php."],
        'Metode tidak didukung.' => ["Method not supported.", "不支持该请求方式。", "このメソッドはサポートされていません。", "الطريقة غير مدعومة.", "Méthode non prise en charge.", "Methode nicht unterstützt.", "Método no admitido."],
        'Permintaan tidak valid.' => ["Invalid request.", "请求无效。", "無効なリクエストです。", "طلب غير صالح.", "Requête non valide.", "Ungültige Anfrage.", "Solicitud no válida."],
        'Sesi Anda sudah berakhir. Muat ulang halaman lalu coba lagi.' => ["Your session has expired. Reload the page and try again.", "您的会话已过期。请刷新页面后重试。", "セッションの有効期限が切れました。ページを再読み込みしてからもう一度お試しください。", "انتهت جلستك. أعد تحميل الصفحة ثم حاول مرة أخرى.", "Votre session a expiré. Rechargez la page puis réessayez.", "Ihre Sitzung ist abgelaufen. Laden Sie die Seite neu und versuchen Sie es erneut.", "Su sesión ha caducado. Recargue la página e inténtelo de nuevo."],
        'Terjadi kesalahan di server. Silakan coba lagi.' => ["A server error occurred. Please try again.", "服务器出错，请重试。", "サーバーでエラーが発生しました。もう一度お試しください。", "حدث خطأ في الخادم. حاول مرة أخرى.", "Une erreur s'est produite sur le serveur. Veuillez réessayer.", "Auf dem Server ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.", "Se produjo un error en el servidor. Inténtelo de nuevo."],
        'Aplikasi sedang dalam pemeliharaan. Silakan coba lagi beberapa saat lagi.' => ["The app is under maintenance. Please try again in a moment.", "应用正在维护中，请稍后再试。", "アプリはメンテナンス中です。しばらくしてからもう一度お試しください。", "التطبيق قيد الصيانة. حاول مرة أخرى بعد قليل.", "L'application est en maintenance. Veuillez réessayer dans quelques instants.", "Die App wird gerade gewartet. Bitte versuchen Sie es in Kürze erneut.", "La aplicación está en mantenimiento. Inténtelo de nuevo en unos momentos."],

        // Halaman pemberitahuan (index.php)
        'Sedang dalam pemeliharaan' => ["Under maintenance", "维护中", "メンテナンス中", "قيد الصيانة", "Maintenance en cours", "Wartungsarbeiten", "En mantenimiento"],
        'Gagal terhubung' => ["Connection failed", "连接失败", "接続に失敗しました", "فشل الاتصال", "Échec de connexion", "Verbindung fehlgeschlagen", "Error de conexión"],
        'Aplikasi tidak dapat terhubung ke database' => ["The app cannot connect to the database", "应用无法连接到数据库", "アプリがデータベースに接続できません", "تعذّر على التطبيق الاتصال بقاعدة البيانات", "L'application ne peut pas se connecter à la base de données", "Die App kann keine Verbindung zur Datenbank herstellen", "La aplicación no puede conectarse a la base de datos"],
        'Periksa kembali data koneksi di file {0}, lalu muat ulang halaman ini.' => ["Check the connection details in the {0} file, then reload this page.", "请检查 {0} 文件中的连接信息，然后刷新此页面。", "{0} ファイルの接続情報を確認してから、このページを再読み込みしてください。", "تحقّق من بيانات الاتصال في الملف {0}، ثم أعد تحميل هذه الصفحة.", "Vérifiez les informations de connexion dans le fichier {0}, puis rechargez cette page.", "Prüfen Sie die Verbindungsdaten in der Datei {0} und laden Sie diese Seite neu.", "Revise los datos de conexión en el archivo {0} y vuelva a cargar esta página."],
        'Detail teknis dicatat di log error server.' => ["Technical details are written to the server error log.", "技术细节已记录在服务器错误日志中。", "技術的な詳細はサーバーのエラーログに記録されています。", "سُجّلت التفاصيل الفنية في سجل أخطاء الخادم.", "Les détails techniques sont consignés dans le journal d'erreurs du serveur.", "Technische Details stehen im Fehlerprotokoll des Servers.", "Los detalles técnicos se registran en el registro de errores del servidor."],
    ];
    if (defined('KPI_I18N_ADMIN')) {
        $m += require __DIR__ . '/i18n_admin.php';
    }
    return $m;
}
