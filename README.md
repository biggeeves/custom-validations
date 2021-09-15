This module validates IDs based on custom rules.


TODO If the minimum allowable characters are 2 and JXT are custom than 1234 still works!  How to handle numbers in this case?
Regular expressions can be used to validate the ID field. 

Create a simple validation that uses the following characters: #, $, and ?<br>
Anything that is not one of those characters will be matched exactly as typed.
Multiple rules can be created.  If the ID matches any rule it will pass validation.
<ul>
<li># = any number</li>
<li>$ = any character a thru z or A thru Z</li>
<li>? = any character</li>
<li>X = the specific character X.  It can Any character other than #, $, and ?.</li>
<li>a = the specific character a.  It can Any character other than #, $, and ?.</li>
</ul>

<strong>Example 1:</strong><br>
\######, must be 5 numbers.  
Valid: 12345<br>
Invalid: A9876

<strong>Example 2:</strong><br>
C####, must start with a C and be followed by 4 numbers.<br> 
Valid: C1234<br>
Invalid: A9876 

<strong>Example 3:</strong><br>
$####, must start with any character (a-z) and be followed by 4 numbers.<br>
Valid: Z1234<br>
Invalid: Ba987 

<strong>Example 4: multiple rules</strong><br>
 ?##,  ?###, ?####, starts with any character beside ? and must be followed by either 2, 3, or 4 numbers. <br>
Valid: _12<br>
Valid: _123<br>
Valid: _1234<br>
Invalid: _A987<br>

<strong>Example 4:</strong><br>
?####, starts with any character beside ? and must be followed by 4 numbers. <br>
Valid: _1234<br>
Invalid: _A987<br>

<h3>Minimum number of characters</h3>
Specify the minimum number of characters that the ID contain.<br> 
Valid: 123<br>
Invalid: 99<br>

<h3>Maximum number of characters</h3>
The maximum number of characters is specified in the REDCap data dictionary.<br> 

<h3>Exact number of characters</h3>
Specify the exact number of characters that the ID contain.<br> 
Valid: R1234<br>
Invalid: V12345<br>

<h3>Regular Expressions</h3>
<p>Regular Expressions can be used to validate the ID format.</p> 
Must start with SW and then have 4 digits. 

Example 4: Contains at least one number between 0 and 9.
Example 3: [0-9]
Valid: vgn1TPa
Invalid: Tpafre

Example 4: Contains between one number and 5 numbers.
Example 3: ^\d{1,5}$
Valid: 45678
Invalid: a
Invalid: a342343

Example 4: Contains 5 numbers
Example 3: ^\d{5}$
Valid: 45678
Invalid: a
Invalid: 123456

Example 4: Starts with C and followed by 5 numbers
Example 3: ^[F]\d{5}$
Valid: F45678
Invalid: A12345
Invalid: F123456

Example 4: Starts with an uppercase letter and followed by 5 numbers
Example 3: ^[A-Z]\d{5}$
Valid: A45678
Invalid: #12345
Invalid: a12345

Example 4: Starts with three uppercase letters and followed by 5 numbers
Example 3: ^[A-Z]{3}\d{5}$
Valid: ABC45678
Invalid: ###12345
Invalid: def12345

Example 4: Starts with a one number between 0 and 9.
Example 3: ^[0-9]
Valid: 1vgnTPa
Invalid: Tpafre

Just the letters a thru z all lower case.
Example 2: ^([a-z]*)$
Valid: asdf
Valid: helloworld
Invalid: hello9

Starts with F or G
Example 2: ^(F|G)
Valid: F1234
Valid: G12
Invalid: Abc345

<strong>Example 1:</strong><br>
Checks that a password has a minimum of 6 characters, at least 1 uppercase letter, 
1 lowercase letter, and 1 number with no spaces.<br>
^((?=\S*?[A-Z])(?=\S*?[a-z])(?=\S*?[0-9]).{6,})\S$

<h3>Rules are always combined</h3>
If the minimum number of characters is 2 and the format "####" is used then both rules are applied and the ID must be 4 digits.<br>
If the minimum number of characters is 7 and the format is "$###" then both rules are applied and the stricter one "$###" means that format must be used.<br>
If the minimum number of characters is 4 and the allowable characters are "JXT" then any combination of the letters J, X and T that is 4 characters long will work.<br> 
Valid: JXXTJJJ<br>
Invalid: JXX<br>

<h3>Invalid Message</h3>
Text that will be displayed to the user as long as ID does not match the format.<br>
<strong>Example:</strong> The ID start with the letter C and be followed by 5 digits.

<h3><em> Valid Message</h3>
Text that is displayed when the ID is in the proper format.<br>
<strong>Example:</strong> Press the submit button to continue.

<h3 style="text-align:center;">Advanced Settings</h3>
<h3>Defining your own characters for $</h3>
The $ defaults to the letters A thru Z both upper case and lower case.<br>  
This can be overwritten by supplying your own characters.<br>
Characters in other languages can be used.<br>

<p><strong>Example 1:</strong><br>
If the only allowable characters are J, X and T. Set the $ = JXT with no spaces between the letters.<br>
Valid letters: J X and or T<br>
Invalid letters: All other letters such as B, E and R.<br></p>

<p><strong>Example 2:</strong><br>
The character &Aacute; and &Yacute; are the only allowable characters.<br>
Valid: &Yacute; and or &Aacute;<br>
Invalid: All other characters.</p>