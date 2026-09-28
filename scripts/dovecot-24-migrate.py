#!/usr/bin/env python3
"""Adapt an emailwiz-generated /etc/dovecot/dovecot.conf to dovecot 2.4.

emailwiz (still) writes dovecot 2.3-era syntax, and dovecot 2.4 on Debian 13
will not start with that file. This script applies the 2.4 equivalents,
idempotently (every patch is an exact-match replacement, so re-running is a
no-op once applied).

Run as root, after emailwiz completes:
    sudo python3 scripts/dovecot-24-migrate.py

Only touches the config if the installed dovecot reports a 2.4 version; on
older hosts it leaves the file alone and exits 0.
"""

import re
import subprocess
import sys

CONF = "/etc/dovecot/dovecot.conf"
BACKUP = "/etc/dovecot/dovecot.conf.emailwiz-backup"


def dovecot_major_minor() -> str | None:
    try:
        out = subprocess.run(
            ["dovecot", "--version"], capture_output=True, text=True, timeout=10
        ).stdout
        m = re.search(r"(\d+)\.(\d+)", out)
        return f"{m.group(1)}.{m.group(2)}" if m else None
    except FileNotFoundError:
        return None


def apply(s: str) -> tuple[str, list[str]]:
    """Return (new_conf, list of what changed)."""
    log: list[str] = []

    def rep(old: str, new: str, note: str):
        nonlocal s
        if old in s:
            s = s.replace(old, new)
            log.append(note)

    # dovecot 2.4 requires a config version marker as the first setting, and a
    # storage version marker. Both must come before line 1 content; comments
    # are allowed in front.
    if "dovecot_config_version =" not in s:
        s = ("dovecot_config_version = 2.4.1\n"
             "dovecot_storage_version = 2.4.1\n" + s)
        log.append("added config/storage version markers")

    version_line = re.search(
        r"^(dovecot_config_version = [0-9.]+)\n?(dovecot_storage_version = [0-9.]+)?",
        s, re.M)
    if version_line and "dovecot_storage_version =" not in s:
        s = s.replace("dovecot_config_version = " +
                      version_line.group(1).split("=")[1].strip() + "\n",
                      version_line.group(1) + "\ndovecot_storage_version = 2.4.1\n", 1)
        log.append("added storage version marker")

    # ssl_cert / ssl_key moved to ssl_server_cert_file / ssl_server_key_file
    rep(
        "ssl_cert = </etc/letsencrypt/live/mail.arezoo.me/fullchain.pem",
        "ssl_server_cert_file = /etc/letsencrypt/live/mail.arezoo.me/fullchain.pem",
        "ssl_cert -> ssl_server_cert_file",
    )
    rep(
        "ssl_key = </etc/letsencrypt/live/mail.arezoo.me/privkey.pem",
        "ssl_server_key_file = /etc/letsencrypt/live/mail.arezoo.me/privkey.pem",
        "ssl_key -> ssl_server_key_file",
    )
    # OpenSSL 3 / dovecot 2.4 defaults replace the old cipher list and DH file
    rep("ssl_cipher_list = EECDH+ECDSA+AESGCM:EECDH+aRSA+AESGCM:EECDH+ECDSA+SHA256:EECDH+aRSA+SHA256:EECDH+ECDSA+SHA384:EECDH+ECDSA+SHA256:EECDH+aRSA+SHA384:EDH+aRSA+AESGCM:EDH+aRSA+SHA256:EDH+aRSA:EECDH:!aNULL:!eNULL:!MEDIUM:!LOW:!3DES:!MD5:!EXP:!PSK:!SRP:!DSS:!RC4:!SEED",
        "# ssl_cipher_list removed (OpenSSL 3 defaults apply in dovecot 2.4)",
        "dropped ssl_cipher_list",
    )
    rep("ssl_prefer_server_ciphers = yes",
        "# ssl_prefer_server_ciphers removed (obsolete in dovecot 2.4)",
        "dropped ssl_prefer_server_ciphers")
    rep("ssl_dh = </usr/share/dovecot/dh.pem",
        "# ssl_dh removed (RFC 7919 defaults apply in dovecot 2.4)",
        "dropped ssl_dh")

    # passdb/userdb blocks now carry their driver as the section name
    rep("userdb {\n\tdriver = passwd\n}", "userdb passwd {\n}",
        "userdb named section")
    rep("passdb {\n\tdriver = pam\n}", "passdb pam {\n}",
        "passdb named section")
    rep("userdb {\n    driver = passwd\n}", "userdb passwd {\n}",
        "userdb named section (spaces)")
    rep("passdb {\n    driver = pam\n}", "passdb pam {\n}",
        "passdb named section (spaces)")

    # mail_location split into mail_driver / mail_path
    rep("mail_location = maildir:~/Mail:INBOX=~/Mail/Inbox:LAYOUT=fs",
        "mail_driver = maildir\nmail_path = ~/Mail",
        "mail_location -> mail_driver/mail_path")
    rep("mail_location = maildir:~/Mail",
        "mail_driver = maildir\nmail_path = ~/Mail",
        "mail_location -> mail_driver/mail_path (simple)")

    # protocols variable dropped in 2.4
    rep("protocols = $protocols  imap pop3 ", "protocols = imap pop3",
        "protocols variable flattened")

    # mail_plugins is a section in 2.4, not an assignment
    rep("protocol lda {\n  mail_plugins = sieve\n}",
        "protocol lda {\n  mail_plugins {\n    sieve = yes\n  }\n}",
        "lda mail_plugins section")
    rep("protocol lmtp {\n  mail_plugins = sieve\n}",
        "protocol lmtp {\n  mail_plugins {\n    sieve = yes\n  }\n}",
        "lmtp mail_plugins section")
    rep("protocol lda {\n\tmail_plugins = sieve\n}",
        "protocol lda {\n\tmail_plugins {\n\t\tsieve = yes\n\t}\n}",
        "lda mail_plugins section (tabs)")

    # pop3_uidl_format used %-variables removed in 2.4; defaults are fine
    rep("""protocol pop3 {
  pop3_uidl_format = %08Xu%08Xv
  pop3_no_flag_updates = yes
}
""",
        "# protocol pop3 left at package defaults (pop3_uidl_format %-variables\n"
        "# were removed in dovecot 2.4; IMAP is the primary protocol here).\n",
        "pop3 custom settings removed")

    # plugin { } block becomes sieve_script sections
    rep("""plugin {
\tsieve = ~/.dovecot.sieve
\tsieve_default = /var/lib/dovecot/sieve/default.sieve
\t#sieve_global_path = /var/lib/dovecot/sieve/default.sieve
\tsieve_dir = ~/.sieve
\tsieve_global_dir = /var/lib/dovecot/sieve/
}""",
        """sieve_script personal {
  driver = file
  path = ~/.sieve
  active_path = ~/.dovecot.sieve
}
sieve_script default {
  type = default
  name = default
  driver = file
  path = /var/lib/dovecot/sieve/default.sieve
}""",
        "plugin{} -> sieve_script sections")

    # old %n username variable: 2.4 variables are %{...}
    rep("auth_username_format = %n", "auth_username_format = %{user|username}",
        "auth_username_format %n -> %{user|username}")

    # refresh stale comment left by emailwiz next to mail_location
    rep("""# Our mail for each user will be in ~/Mail, and the inbox will be ~/Mail/Inbox
# The LAYOUT option is also important because otherwise, the boxes will be `.Sent` instead of `Sent`.
""",
        """# Our mail for each user lives in ~/Mail (maildir). dovecot 2.4 splits the
# old mail_location setting into mail_driver + mail_path.
""",
        "stale LAYOUT comment refreshed")

    return s, log


def main() -> int:
    ver = dovecot_major_minor()
    if not ver:
        print("dovecot binary not found; nothing to do", file=sys.stderr)
        return 0
    if ver != "2.4":
        print(f"dovecot {ver} — no 2.4 migration needed", file=sys.stderr)
        return 0

    try:
        original = open(CONF).read()
    except FileNotFoundError:
        print(f"{CONF} not found; nothing to do", file=sys.stderr)
        return 0

    new_conf, changes = apply(original)
    if changes:
        import shutil
        if not os.path.exists(BACKUP):
            shutil.copy2(CONF, BACKUP)
        open(CONF, "w").write(new_conf)
        print("adapted /etc/dovecot/dovecot.conf:")
        for c in changes:
            print("  -", c)
    else:
        print("already adapted; no changes")

    # validate: non-zero exit from `dovecot -n` means a config error
    r = subprocess.run(["dovecot", "-n"], capture_output=True, text=True)
    if r.returncode != 0:
        print("config validation FAILED:", file=sys.stderr)
        print(r.stderr, file=sys.stderr)
        return 1
    print("config validation OK")
    return 0


if __name__ == "__main__":
    import os
    sys.exit(main())