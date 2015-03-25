package com.piwik.intellijplugins.piwikstorm.inspections.tests;

import com.intellij.codeInspection.LocalInspectionTool;
import com.intellij.openapi.application.ex.PathManagerEx;
import com.intellij.openapi.vfs.LocalFileSystem;
import com.intellij.openapi.vfs.VirtualFile;
import com.intellij.testFramework.InspectionTestCase;
import com.intellij.testFramework.PsiTestUtil;
import com.piwik.intellijplugins.piwikstorm.inspections.PiwikNonApiInspection;
import org.jetbrains.annotations.NonNls;

import java.io.File;
import java.net.URL;

public class PiwikNonApiInspectionTest extends InspectionTestCase
{
    public void test_Inspection_NoticesAllUsesOfNonApiCode() throws Exception {
        super.doTest("nonApi/allUsingNonApiCases", getInspection());
    }

    public void test_Inspection_SucceedsWhenCodeUsesOnlyApiCode() throws Exception {
        super.doTest("nonApi/allUsingApiCases", getInspection());
    }

    private LocalInspectionTool getInspection() {
        return new PiwikNonApiInspection();
    }

    protected void setupRootModel(String testDir, VirtualFile[] sourceDir, String sdkName) {
        super.setupRootModel(testDir, sourceDir, sdkName);

        File mockPiwikCodePath = new File(testDir + "/../../../mockPiwikCode");
        VirtualFile mockPiwikCode =  LocalFileSystem.getInstance().refreshAndFindFileByIoFile(mockPiwikCodePath);
        if (mockPiwikCode == null) {
            assertNotNull("could not find mockPiwikCode dir at " + mockPiwikCodePath, mockPiwikCode);
        }

        PsiTestUtil.addSourceRoot(this.myModule, mockPiwikCode);
    }

    @NonNls
    protected String getTestDataPath() {
        ClassLoader thisClassLoader = PiwikNonApiInspectionTest.class.getClassLoader();
        URL resourceUrl = PiwikNonApiInspectionTest.class.getResource("/mockPiwikCode/PiwikCode.php");

        String testDataPath;
        try {
            File resourcePath = new File(resourceUrl.toURI());
            testDataPath = resourcePath.getParentFile().getParentFile().toString();
        } catch (Exception ex) {
            testDataPath = PathManagerEx.getTestDataPath();
        }

        return testDataPath + "/inspection/";
    }
}
