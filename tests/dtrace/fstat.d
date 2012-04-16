#!/usr/sbin/dtrace -sq
/**
 * dtrace script for counting fstat calls
 *
 * @package    phpMyFAQ
 * @subpackage Tests
 * @author     David Soria Parra <dsp@php.net>
 * @since      2009-04-20
 * @license    New BSD License
 * @version    SVN: $Id$
 * @copyright  2009 David Soria Parra
 *
 * Copyright (c) 2009, David Soria Parra <dsp@php.net>
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the <organization> nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY <copyright holder> ''AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <copyright holder> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
 
php*:::execute-entry
{
	self->isInsidePHP++;
	self->filename = copyinstr(arg0);
	self->lineno = arg1;
}

php*:::execute-return
{
	self->isInsidePHP--;
}

syscall::fstat:entry
/self->isInsidePHP > 0/
{
	self->timestamp = timestamp;
}

syscall::fstat:return
/self->isInsidePHP > 0/
{
	@[self->filename, self->lineno] = count();
	@quantize[self->filename, self->lineno] = quantize(timestamp - self->timestamp);
}
