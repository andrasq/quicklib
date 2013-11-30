/* @(#)ascii.c - UCB 'ascii' program
 * 941,19w AR.
 */

#include <stdio.h>
#include <termios.h>
#include <unistd.h>

static struct termios save_termios;
static enum { RESET, RAW, CBREAK } ttystate = RESET;
static int ttysavefd = -1;

/*
 * From Stevens, p. 354
 */
int tty_cbreak( int fd )
{
    struct termios buf;
    if (tcgetattr(fd, &save_termios) < 0) return -1;

    buf = save_termios;			/* structure copy */
    buf.c_lflag &= ~(ECHO | ICANON);	/* echo off, canonical mode off */
    buf.c_cc[VMIN] = 1;			/* 1 byte at a time, */
    buf.c_cc[VTIME] = 0;		/*   no timer */

    if (tcsetattr(fd, TCSAFLUSH, &buf) < 0) return -1;
    ttystate = CBREAK;
    ttysavefd = fd;

    return 0;
}


/*
 * From Stevens, p. 355
 */
int tty_raw( int fd )
{
    struct termios buf;

    if (tcgetattr(fd, &save_termios) < 0) return -1;

    buf = save_termios;			/* structure copy */
    buf.c_lflag &= ~(ECHO | ICANON | IEXTEN | ISIG);
    buf.c_iflag &= ~(BRKINT | ICRNL | INPCK | ISTRIP | IXON);
    buf.c_cflag &= ~(CSIZE | PARENB);
    buf.c_cflag |= CS8;
    /* buf.c_oflag &= ~(OPOST);		/* AR: keep output processing */
    buf.c_cc[VMIN] = 1;			/* 1 byte at a time, */
    buf.c_cc[VTIME] = 0;		/*   no timer */

    if (tcsetattr(fd, TCSAFLUSH, &buf) < 0) return -1;
    ttystate = RAW;
    ttysavefd = fd;

    return 0;
}


/*
 * From Stevens, p. 355
 */
int tty_reset( int fd )
{
    if (ttystate != CBREAK && ttystate != RAW) return 0;
    if (tcsetattr(fd, TCSAFLUSH, &save_termios) < 0) return -1;
    ttystate = RESET;
    return 0;
}


int main( int ac, char *av[] )
{
    unsigned char c0, c1, c2;
    int done = 0;

    /* put terminal into cbreak mode, getting each keystroke separately */
    if (tty_raw(0)) return 1;

    /* for each keystroke: */
    while (!done) {
	c0 = c1;
	c1 = c2;

	read(0,&c2,1);
	{
	    char img[5];
	    if (isprint(c2) || (unsigned)c2 > 128)
		sprintf(img," %c ",c2);
	    else if (iscntrl(c2))
		sprintf(img," ^%c",c2-1+'A');
	    else
		sprintf(img," . ");
	    /*Format:  []'A'  165   0xxx   0x41[] */
	    printf("%s  %3d   0%03o   0x%02x\n", img, (unsigned)c2, c2, c2);
	}


	/* print the character, and its decimal, octal, and hex value */

	if (c0 == c1 && c1 == c2) done = 1;
    }

    /* if a keystroke is repeated 3 times,
     * restore the terminal modes and stop */
    tty_reset(0);

    return 0;
}
