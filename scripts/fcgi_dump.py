#!/usr/bin/env python3
"""A minimal FastCGI server that prints the PARAMS it was handed, then answers.

Exists to answer one question that cannot be settled by reading documentation:
when nginx is configured with `fastcgi_param HTTP_X_FORWARDED_PROTO $scheme`, and
a visitor sends their own `X-Forwarded-Proto` header, does the application receive
one value or two — and if two, which one?

nginx passes a client's request headers to FastCGI automatically. If that is true,
a config that also sets the same name explicitly produces two PARAMS entries with
the same name, and which one PHP keeps is decided by the SAPI, not by nginx. That
is worth knowing rather than assuming, because the whole point of setting the
header from $scheme is that a visitor cannot influence it.

The FastCGI wire format is small enough to implement directly:

    record header, 8 bytes
        version:1  type:1  requestId:2  contentLength:2  paddingLength:1  reserved:1
    PARAMS content
        nameLength:1  valueLength:1  name  value     (each padded to 8-byte boundary)

Usage:  ./fcgi_dump.py <port>
"""
import socket
import struct
import sys
import os

FCGI_BEGIN_REQUEST, FCGI_END_REQUEST = 1, 3
FCGI_PARAMS, FCGI_STDIN = 4, 5
FCGI_STDOUT, FCGI_STDERR = 6, 7
FCGI_RESPONDER = 1


def read_exactly(sock, n):
    buf = b""
    while len(buf) < n:
        chunk = sock.recv(n - len(buf))
        if not chunk:
            return None
        buf += chunk
    return buf


def read_record(sock):
    head = read_exactly(sock, 8)
    if head is None:
        return None
    version, rtype, rid, clen, plen, _ = struct.unpack("!BBHHBB", head)
    content = read_exactly(sock, clen) if clen else b""
    if plen:
        read_exactly(sock, plen)
    return rtype, rid, content


def parse_params(data):
    """Turns a PARAMS payload into a list, preserving order and duplicates.

    Order matters and duplicates are the point: a dict would silently hide the
    collision this script exists to detect.

    Each name and each value is NUL-padded independently to the next 8-byte
    boundary. Padding the pair rather than each is the mistake that
    desynchronises every parameter after the first.
    """
    out, i = [], 0
    while i < len(data):
        if data[i] & 0x80:                      # length needs 4 bytes
            name_len = struct.unpack("!I", data[i:i + 4])[0] & 0x7FFFFFFF
            i += 4
        else:
            name_len = data[i]
            i += 1
        if data[i] & 0x80:
            value_len = struct.unpack("!I", data[i:i + 4])[0] & 0x7FFFFFFF
            i += 4
        else:
            value_len = data[i]
            i += 1
        name = data[i:i + name_len].decode("latin-1")
        i += name_len + (-name_len % 8)
        value = data[i:i + value_len].decode("latin-1")
        i += value_len + (-value_len % 8)
        out.append((name, value))
    return out


def pack_record(rtype, rid, content=b""):
    return struct.pack("!BBHHBB", 1, rtype, rid, len(content), 0, 0) + content


def main():
    port = int(sys.argv[1]) if len(sys.argv) > 1 else 9000
    srv = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    srv.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    bind = os.environ.get("FCGI_BIND", "127.0.0.1")
    srv.bind((bind, port))
    srv.listen(16)
    print(f"fcgi_dump listening on {bind}:{port}", flush=True)

    while True:
        conn, _ = srv.accept()
        params, rid = [], 0
        try:
            while True:
                rec = read_record(conn)
                if rec is None:
                    break
                rtype, rid, content = rec
                if rtype == FCGI_BEGIN_REQUEST:
                    # Nothing is sent back here. FCGI_END_REQUEST is the last
                    # record of the whole exchange, so answering the
                    # begin-request record with one is what made nginx report
                    # "unexpected FastCGI record: 3 while reading response header".
                    pass
                elif rtype == FCGI_PARAMS:
                    if content:
                        params.extend(parse_params(content))
                    else:
                        # Empty PARAMS marks the end of the parameter stream.
                        body = ""
                        seen = {}
                        for n, v in params:
                            seen.setdefault(n, []).append(v)
                        dupes = {n: v for n, v in seen.items() if len(v) > 1}
                        body += "PARAMS (in order):\n"
                        for n, v in params:
                            body += f"  {n} = {v}\n"
                        body += "\n"
                        if dupes:
                            body += "DUPLICATE PARAM NAMES:\n"
                            for n, v in dupes.items():
                                body += f"  {n} -> {v}\n"
                            body += "\nLAST occurrence is what PHP's SAPI keeps.\n"
                        else:
                            body += "No duplicate names.\n"
                        # Only one of these is real; the other's value is a
                        # placeholder used purely to identify the record type.
                        out = pack_record(FCGI_STDOUT, rid,
                                          (b"Content-Type: text/plain\r\n\r\n" + body.encode()))
                        out += pack_record(FCGI_STDOUT, rid, b"")
                        out += pack_record(FCGI_END_REQUEST, rid,
                                           struct.pack("!IBBBB", 0, FCGI_RESPONDER, 0, 0, 0))
                        conn.sendall(out)
                elif rtype == FCGI_STDIN:
                    if not content:
                        break
        except (ConnectionResetError, BrokenPipeError):
            pass
        finally:
            conn.close()


if __name__ == "__main__":
    main()
