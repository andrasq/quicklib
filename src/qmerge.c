/**
 * Quick little program to merge sorted files and retain the sorted order.
 * The merge can be a union (default), intersection, or set difference.
 * One or more sorted source files can be specified on the command line.
 * One of the files may be stdin, represented by the special filename -.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-03-02 - AR.
 */

#define VERSION "v0.10"

static char __id[] = "@(#)qmerge " VERSION " -- and/or/not merge files and produce sorted output\n";

#include <stdio.h>
#include <unistd.h>
#include <stdlib.h>
#include <string.h>
#include <stdarg.h>

static int _op = 'o';
static int _numeric = 0;
static int _unique = 0;


typedef struct _filebuf {
    FILE *fp;                   // file being read
    char *name;                 // name of file
    char line[100000];          // current line
    long long ival;             // current integer value of line (-n)
    int eof;
} File;


static void die( char *fmt, ... )
{
    va_list ap;
    va_start(ap, fmt);
    vfprintf(stderr, fmt, ap);
    if (fmt[strlen(fmt)-1] != '\n') fprintf(stderr, "\n");
    exit(2);
}

static void * emalloc( size_t count, size_t size )
{
    void *p = calloc(count, size);
    if (!p) die("calloc(%lu,%lu): out of memory", count, size);
    return p;
}


inline int compar_numeric( File *a, File *b )
{
    return a->ival - b->ival;
}

inline int compar_string( File *a, File *b )
{
    return strcmp(a->line, b->line);
}

inline int compar_numeric_reverse( File *a, File *b )
{
    return b->ival - a->ival;
}

inline int compar_string_reverse( File *a, File *b )
{
    return strcmp(b->line, a->line);
}



static inline int file_feof( File *f )
{
    return f->eof;
}

static void file_readline( File *f )
{
    char line[100000];
    line[sizeof(line)-1] = 'x';

    if (!fgets(line, sizeof(line), f->fp)) {
        if (!feof(f->fp)) die("%s: read error", f->name);
        f->eof = 1;
        return;
    }
    else if (!line[sizeof(line)-1]) {
        /* line too long */
        die("%s: line too long, max is 100k", f->name);
    }
    strcpy(f->line, line);
    if (_numeric) f->ival = strtoll(line, NULL, 10);
}

static void file_writeline( File *f, const char *line )
{
    int d;
    if (!_unique) {
        if (fputs(line, f->fp) == EOF) die("%s: write error", f->name);
    }
    else if ((d = strcmp(f->line, line)) != 0) {
        if (fputs(line, f->fp) == EOF) die("%s: write error", f->name);
        strcpy(f->line, line);
    }
}


int file_merge_or( File **files, int nfiles, File *outfile, int (*compar)(File*, File*) )
{
    int i, lo;

    while (nfiles > 0) {
        lo = -1;
        for (i=0; i<nfiles; i++) {
            if (file_feof(files[i])) {
                files[i] = files[nfiles-1];
                nfiles -= 1;
                i--;
                continue;
            }
            if (lo < 0 || (*compar)(files[lo], files[i]) > 0)
                lo = i;
        }
        if (lo >= 0) {
            file_writeline(outfile, files[lo]->line);
            file_readline(files[lo]);
        }
    }
    return 0;
}

int file_merge_and( File **files, int nfiles, File *outfile, int (*compar)(File*, File*) )
{
    int i, same;
    int d;

    while (nfiles > 0) {
        same = 1;
        if (file_feof(files[0])) return 0;
        for (i=1; i<nfiles; i++) {
            if (file_feof(files[i])) return 0;
            if ((d = (*compar)(files[0], files[i])) < 0) {
                file_readline(files[0]);
                if (file_feof(files[0])) return 0;
                same = 0;
            }
            else if (d > 0) {
                file_readline(files[i]);
                if (file_feof(files[i])) return 0;
                same = 0;
            }
        }
        if (same) {
            file_writeline(outfile, files[0]->line);
            for (i=0; i<nfiles; i++) file_readline(files[i]);
        }
    }
    return 0;
}

int file_merge_not( File **files, int nfiles, File *outfile, int (*compar)(File*, File*) )
{
    int i;
    File *master = files[0];
    int d;

    while (nfiles > 0) {
        if (file_feof(master)) return 0;
        for (i=1; i<nfiles; i++) {
            if (file_feof(files[i])) continue;
            if ((d = (*compar)(master, files[i])) == 0) {
                // omit matching elements from results
                file_readline(master);
                if (file_feof(master)) return 0;
                // if master element changed, recheck against all other lists
                i = 0;
                continue;
            }
            else if (d > 0) {
                // subtrahend too small, read more
                file_readline(files[i]);
                i--;
                continue;
            }
        }
        // if successfully passed the screen, emit result
        file_writeline(outfile, master->line);
        file_readline(master);
    }
    return 0;
}

int main( int ac, char *av[] )
{
    int c;
    File **files, *outfile;
    int e, nfiles;

    if (strcmp(av[1], "--help") == 0)
        av[1] = "-h";

    while ((c = getopt(ac, av, "aovhnu")) > 0) {
        switch (c) {
        case 'a':       // and merge (intersection)
        case 'o':       // or merge (union)
        case 'v':       // not merge (difference)
            _op = c;
            break;
        case 'h':
            printf("%s", __id+4);
            printf("usage:  qmerge [-n] [-u] [-a|-o|-v] file [file ...]\n");
            exit(0);
        case 'n':       // numeric comparisons
            _numeric = 1;
            break;
        case 'u':       // unique only
            _unique = 1;
            break;
        default:
            die("type -h for help");
        }
    }

    ac -= optind;
    av += optind;

    if (!ac) {
        files = emalloc(1, sizeof(File*));
        files[0] = emalloc(1, sizeof(File));
        files[0]->name = "<stdin>";
        files[0]->fp = stdin;
        files[0]->ival = 0;
        strcpy(files[0]->line, "");
        file_readline(files[0]);
        nfiles = 1;
    }
    else {
        int i;
        files = emalloc(ac, sizeof(*files));
        for (i=0; i<ac; i++) {
            files[i] = emalloc(1, sizeof(**files));
            files[i]->name = av[i];
            files[i]->ival = 0;
            strcpy(files[i]->line, "");
            files[i]->fp = strcmp(av[i], "-") == 0 ? stdin : fopen(av[i], "r");
            if (!files[i]->fp) die("%s: unable to open file", av[i]);
            file_readline(files[i]);
        }
        nfiles = ac;
    }

    outfile = emalloc(1, sizeof(File));
    outfile->fp = stdout;
    outfile->name = "<stdout>";
    strcpy(outfile->line, "");

    switch (_op) {
    case 'a':
        e = file_merge_and(files, nfiles, outfile, _numeric ? compar_numeric : compar_string);
        break;
    case 'o':
        e = file_merge_or(files, nfiles, outfile, _numeric ? compar_numeric : compar_string);
        break;
    case 'v':
        e = file_merge_not(files, nfiles, outfile, _numeric ? compar_numeric : compar_string);
        break;
    default:
        die("%c: invalid operation", _op);
        break;
    }

    return 0;
}
