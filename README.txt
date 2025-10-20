iAssign version 2.1.70
------------------------

By iMatica/iMath - free interactive tools for teaching-learning Math
   Patricia Rodrigues <patricnet@ig.com.br>, Leônidas O. Brandão <leo@ime.usp.br>, Igor Félix <igormf@ime.usp.br>


.: About iAssign :.

This is the iAssign (Interactive Assignment) package, an iMath free system to enrich activities in Moodle. 
It is created by Patricia Rodrigues and Leônidas de Oliveira Brandão.

iAssign's goal is to increase interactivity in activities related to specific subjects (such as Geometry, Functions, Programming,...)
in a flexible way. 

In order to improve interactivity, iAssign makes use of iLM (interactive Learning Module),
that is any interactive tool that runs under a Web browser.
Typically an iLM is a Java applet or an HTML5 package with a few (mandatory) communication methods, all based on HTTP protocol.
This implies that any applet can easily became an iLM and can be integrated to Moodle under iAssign package. 

If the iLM offers automatic assessment functionality, iAssign is able
to deal with it. Under such iLM, iAssign provides immediate feedback to
the student, and the teachers can get instant information about their
activities (including reports about the student performance).

It can be added new iLM into iAssign, at any time, but (for security
reason), only the administrator has the privilege of integrating new iLM into iAssign.
Once integrated, an iLM can be used by anyone registered in its Moodle.
For instance, an user with privileges of "teacher" is allowed to use
the iAssign authoring tools to create activities with any iLM
(like iGeom, iGraf, or iVprog, respectively to related to the subjects, Geometry, Functions and Programming).

The main features of iAssign package are:
 - The authoring tool to allow any teacher to easily prepare activities to students. Activities can be:
   + an exercise (the student must send an answer, and if the iLM has automatic assessment, its results (right/wrong) is also registered);
   + a test (the student does the activity, if iLM has automatic assessment, the student gets immediate feedback, but no data is recorded in Moodle's database);
   + an example (the student can interact with the example, but nothing is recorded).
 - Reports about students activities: 
   + teachers can see, e.g., a survey or statistics about student's answers and can have quick access to any submited answer;
   + the students have a survey of their activities (including their grades)
 - Integration with general Moodle grades
 - A filter that allows the insertion of iLM content into any (asynchronous) Moodle text.

Besides, as the majority of Moodle modules, iAssign can export (as backup) one activity or a complete lesson (a set of activities).

Note: This plugin is part of iAssign SET see more in https://moodle.org/plugins/browse.php?list=set&id=54

.: Quick install instructions (to be used by the system administrator) :.

0) Be sure you have (at least) Moodle 2.4 until Moodle 2.6 installed
1) Be sure to have the latest language package, such as English (en), Portuguese (pt_br).
2) Be sure to have the latest version of the module iAssign (see in https://github.com/leonidasbrandao/iAssign master)
3) Unpack 'iAssign.2.1.x.zip' (or 'iAssign.2.1.x.tgz')
4) Copy the 'iassign' module directory into the "mod" subdirectory of Moodle installation
5) Under role "administrator", go to the 'Site administration' and click on 'Notifications'
6) Have fun.


.: What is news :.

Considering the iAssign for Moodle 1.9, the new featuress in this version are:

 - the teacher can produce a new interactive activity directly "on-line", using the new iLM editor (but is still possible to upload files);
 - in a course with more than one teacher, it is now possible to use a local repository.
 - See more in http://docs.moodle.org/en/iAssign.


Comments and suggestions are always welcome at http://www.matematica.br/iassign or https://github.com/leonidasbrandao/iAssign.
(if the comment area is missing, please send us an email).


Best regards,

Leônidas <leo@ime.usp.br> and Patricia <patricnet@ig.com.br>

Institute of Mathematics and Statistics - University of São Paulo
iMath/LInE : http://www.matematica.br : http://line.ime.usp.br
